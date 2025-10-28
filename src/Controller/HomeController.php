<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        return $this->render('pages/index.html.twig');
    }

    #[Route('/signup', name: 'app_signup', methods: ['GET','POST'])]
    public function signup(
        Request $request,
        SessionInterface $session
    ): Response {
        $formData = [
            'username' => '',
            'email' => '',
            'password' => '',
            'confirmPassword' => '',
        ];
        $errors = [];

        if ($request->isMethod('POST')) {
            $formData['username'] = trim(
                $request->request->get('username', '')
            );
            $formData['email'] = trim($request->request->get('email', ''));
            $formData['password'] = $request->request->get('password', '');
            $formData['confirmPassword'] = $request->request->get(
                'confirmPassword',
                ''
            );

            // Validation
            if (!$formData['username']) {
                $errors['username'] = 'Username is required.';
            }
            if (!$formData['email']) {
                $errors['email'] = 'Email is required.';
            } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Please enter a valid email address.';
            }
            if (!$formData['password']) {
                $errors['password'] = 'Password is required.';
            } elseif (strlen($formData['password']) < 6) {
                $errors['password'] = 'Password must be at least 6 characters.';
            }
            if (!$formData['confirmPassword']) {
                $errors['confirmPassword'] = 'Please confirm your password.';
            } elseif ($formData['password'] !== $formData['confirmPassword']) {
                $errors['confirmPassword'] = 'Passwords do not match.';
            }

            // Check if email already exists
            $users = $session->get('users', []);
            foreach ($users as $user) {
                if (
                    strtolower($user['email']) ===
                    strtolower($formData['email'])
                ) {
                    $this->addFlash('error', 'Email is already registered.');
                    $errors['email'] = 'Email is already registered.';
                    break;
                }
            }

            // If no errors, save user and redirect
            if (empty($errors)) {
                $users[] = [
                    'username' => $formData['username'],
                    'email' => $formData['email'],
                    'password' => $formData['password'],
                ];
                $session->set('users', $users);
                $session->set('is_logged_in', true);
                $session->set('user_email', $formData['email']);

                $this->addFlash('success', 'Account created successfully!');

                return $this->redirectToRoute('dashboard');
            }
        }

        return $this->render('pages/signup.html.twig', [
            'formData' => $formData,
            'errors' => $errors,
        ]);
    }

    #[Route('/login', name: 'login', methods: ['GET', 'POST'])]
    public function login(Request $request, SessionInterface $session): Response
    {
        $email = '';
        $errors = [];

        if ($request->isMethod('POST')) {
            $email = trim($request->request->get('email'));
            $password = $request->request->get('password');

            // ✅ 1. Validation
            if (empty($email)) {
                $errors['email'] = 'Email or username is required.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Please enter a valid email address.';
            }

            if (empty($password)) {
                $errors['password'] = 'Password is required.';
            } elseif (strlen($password) < 6) {
                $errors['password'] = 'Password must be at least 6 characters.';
            }

            // ✅ 2. If no validation errors
            if (empty($errors)) {
                $fakeUser = [
                    'email' => 'demo@example.com',
                    'password' => 'password123',
                ];

                // Normally you'd fetch this from a DB.
                $storedUsers = $session->get('users', []);
                $userFound = null;

                foreach ($storedUsers as $u) {
                    if ($u['email'] === $email) {
                        $userFound = $u;
                        break;
                    }
                }

                // ✅ 3. Check credentials
                if (
                    ($userFound && $password === $userFound['password']) ||
                    ($email === $fakeUser['email'] &&
                        $password === $fakeUser['password'])
                ) {
                    // ✅ 4. Save session
                    $session->set('is_logged_in', true);
                    $session->set('user_email', $email);

                    // Flash message (like toast)
                    $this->addFlash('success', 'Login successful!');

                    return $this->redirectToRoute('dashboard');
                } else {
                    $this->addFlash(
                        'error',
                        'Invalid credentials. Please try again.'
                    );
                }
            }
        }

        return $this->render('pages/login.html.twig', [
            'email' => $email,
            'errors' => $errors,
        ]);
    }

    #[Route('/dashboard', name: 'dashboard')]
    public function dashboard(SessionInterface $session): Response
    {
        if (!$session->get('is_logged_in')) {
            $this->addFlash('error', 'You must log in first.');
            return $this->redirectToRoute('login');
        }

        return $this->render('pages/dashboard.html.twig', [
            'user_email' => $session->get('user_email'),
        ]);
    }

    #[Route('/logout', name: 'logout', methods: ['POST'])]
    public function logout(
        Request $request,
        SessionInterface $session
    ): Response {
        $session->clear();
        $this->addFlash('success', 'You have logged out successfully.');

        return $this->redirectToRoute('login');
    }
}
