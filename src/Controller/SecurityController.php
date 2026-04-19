<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserRegistrationType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use App\Repository\UserRepository;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login', methods: ['GET', 'POST'])]
    public function login(Request $request, SessionInterface $session, UserRepository $userRepository, UserPasswordHasherInterface $passwordHasher, MailerInterface $mailer): Response
    {
        $error = null;

        if ($request->isMethod('POST')) {
            $email = trim((string) $request->request->get('email'));
            $password = (string) $request->request->get('password');
            $user = $userRepository->findOneBy(['email' => $email]);

            if (!$user || !$passwordHasher->isPasswordValid($user, $password)) {
                $error = 'Invalid email or password.';
            } else {
                $code = $this->generateVerificationCode();
                $session->set('login_verification_user_id', $user->getId());
                $session->set('login_verification_code', $code);
                $session->set('login_verification_expires_at', (new \DateTimeImmutable('+15 minutes'))->format(\DateTimeInterface::ATOM));
                $this->sendVerificationEmail($mailer, $user->getEmail(), $code, 'Login verification code');

                $this->addFlash('success', 'A login verification code has been sent to your email.');

                return $this->redirectToRoute('app_login_verify');
            }
        }

        return $this->render('security/login.html.twig', [
            'last_username' => $request->request->get('email', ''),
            'error' => $error,
        ]);
    }

    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher, MailerInterface $mailer): Response
    {
        $user = new User();
        $form = $this->createForm(UserRegistrationType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $user->getEmail()]);

            if ($existingUser) {
                $form->get('email')->addError(new FormError('This email is already registered.'));

                return $this->render('security/register.html.twig', [
                    'registrationForm' => $form,
                ]);
            }

            $plainPassword = (string) $form->get('plainPassword')->getData();
            $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));
            $user->setEmailVerifiedAt(new \DateTimeImmutable());
            $user->setEmailVerificationCode(null);
            $user->setEmailVerificationExpiresAt(null);

            $entityManager->persist($user);
            $entityManager->flush();

            $this->sendWelcomeEmail($mailer, $user->getEmail(), $user->getFirstName());

            $this->addFlash('success', 'Account created successfully. Check your email for the welcome message.');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }

    #[Route('/login/verify', name: 'app_login_verify', methods: ['GET', 'POST'])]
    public function verifyLogin(Request $request, SessionInterface $session, UserRepository $userRepository): Response
    {
        $userId = $session->get('login_verification_user_id');
        $storedCode = $session->get('login_verification_code');
        $expiresAt = $session->get('login_verification_expires_at');

        if (!$userId || !$storedCode || !$expiresAt) {
            return $this->redirectToRoute('app_login');
        }

        $user = $userRepository->find($userId);

        if (!$user) {
            $this->clearLoginVerificationSession($session);

            return $this->redirectToRoute('app_login');
        }

        $error = null;

        if ($request->isMethod('POST')) {
            $submittedCode = trim((string) $request->request->get('code'));
            $expires = new \DateTimeImmutable((string) $expiresAt);

            if ($expires < new \DateTimeImmutable()) {
                $error = 'The login code has expired. Please login again.';
            } elseif ($submittedCode !== (string) $storedCode) {
                $error = 'Invalid login verification code.';
            } else {
                $this->clearLoginVerificationSession($session);
                $this->completeLogin($user, $session);

                $this->addFlash('success', 'Login verified successfully.');

                return $this->redirectToRoute('app_home');
            }
        }

        return $this->render('security/verify_code.html.twig', [
            'title' => 'Verify your login',
            'description' => 'Enter the code sent to your email to complete the sign-in.',
            'error' => $error,
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    private function generateVerificationCode(): string
    {
        return (string) random_int(100000, 999999);
    }

    private function sendVerificationEmail(MailerInterface $mailer, string $recipientEmail, string $code, string $subject): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address('no-reply@example.com', 'Event Manager'))
            ->to($recipientEmail)
            ->subject($subject)
            ->htmlTemplate('security/email_verification.html.twig')
            ->context([
                'code' => $code,
            ]);

        $mailer->send($email);
    }

    private function sendWelcomeEmail(MailerInterface $mailer, string $recipientEmail, string $firstName): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address('no-reply@example.com', 'Event Manager'))
            ->to($recipientEmail)
            ->subject('Welcome to Event Manager')
            ->htmlTemplate('security/welcome_email.html.twig')
            ->context([
                'firstName' => $firstName,
            ]);

        $mailer->send($email);
    }

    private function completeLogin(User $user, SessionInterface $session): void
    {
        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());

        $this->container->get('security.token_storage')->setToken($token);
        $session->set('_security_main', serialize($token));
    }

    private function clearLoginVerificationSession(SessionInterface $session): void
    {
        $session->remove('login_verification_user_id');
        $session->remove('login_verification_code');
        $session->remove('login_verification_expires_at');
    }
}
