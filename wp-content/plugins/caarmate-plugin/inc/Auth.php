<?php

declare(strict_types=1);

/**
 * Auth — Frontend registration and login forms for CaarMate.
 *
 * Provides [cm_auth_forms] shortcode and [cm_header_nav] shortcode.
 * Handles POST-based user registration with role selection.
 *
 * @package CaarMate\Core
 */

namespace CaarMate\Core;

class Auth
{
    /**
     * Wire hooks and shortcodes.
     *
     * @return void
     */
    public function register(): void
    {
        add_shortcode('cm_auth_forms', [$this, 'renderAuthForms']);
        add_shortcode('cm_header_nav', [$this, 'renderHeaderNav']);

        // Intercept registration POST before headers are sent.
        add_action('template_redirect', [$this, 'handleRegistration']);
        add_action('template_redirect', [$this, 'handleLogin']);
    }

    // =========================================================================
    //  [cm_header_nav] — Auth-aware navigation
    // =========================================================================

    /**
     * Render auth-aware navigation links.
     *
     * Guests:     Home | Find Rides | Log In | Register
     * Logged-in:  Home | Find Rides | Dashboard | Log Out
     *
     * @param array|string $atts Shortcode attributes.
     * @return string Escaped HTML.
     */
    public function renderHeaderNav($atts = []): string
    {
        $html = '<nav class="cm-header-links">';
        $html .= '<a href="' . esc_url(home_url('/')) . '" class="cm-nav-link">'
            . esc_html__('Home', 'caarmate') . '</a>';
        $html .= '<a href="' . esc_url(home_url('/rides/')) . '" class="cm-nav-link">'
            . esc_html__('Find Rides', 'caarmate') . '</a>';

        if (is_user_logged_in()) {
            $html .= '<a href="' . esc_url(home_url('/dashboard/')) . '" class="cm-nav-link cm-nav-cta">'
                . esc_html__('Dashboard', 'caarmate') . '</a>';
        } else {
            $html .= '<a href="' . esc_url(home_url('/login/')) . '" class="cm-nav-link">'
                . esc_html__('Log In', 'caarmate') . '</a>';
            $html .= '<a href="' . esc_url(home_url('/register/')) . '" class="cm-nav-link cm-nav-cta">'
                . esc_html__('Register', 'caarmate') . '</a>';
        }

        $html .= '</nav>';

        // Hamburger button for mobile
        $html .= '<button class="cm-hamburger" aria-label="Menu" onclick="document.querySelector(\'.cm-header-links\').classList.toggle(\'cm-nav-open\')">';
        $html .= '<span></span><span></span><span></span>';
        $html .= '</button>';

        return $html;
    }

    // =========================================================================
    //  [cm_auth_forms] — Registration / Login UI
    // =========================================================================

    /**
     * Render the auth forms (tabbed: Login / Register).
     *
     * @param array|string $atts Shortcode attributes.
     * @return string Escaped HTML.
     */
    public function renderAuthForms($atts = []): string
    {
        // If already logged in, redirect message.
        if (is_user_logged_in()) {
            return '<div class="cm-auth-logged-in">'
                . '<p>' . esc_html__('You are already logged in.', 'caarmate') . '</p>'
                . '<a href="' . esc_url(home_url('/dashboard/')) . '" class="cm-btn cm-btn-primary">'
                . esc_html__('Go to Dashboard', 'caarmate') . '</a>'
                . '</div>';
        }

        $activeTab = isset($_GET['tab']) && $_GET['tab'] === 'register' ? 'register' : 'login';
        $error = isset($_GET['auth_error']) ? sanitize_text_field(wp_unslash($_GET['auth_error'])) : '';
        $success = isset($_GET['auth_success']) ? true : false;

        $html = '<div class="cm-auth-container">';

        // Error / success messages
        if ($error) {
            $errorMessages = [
                'email_exists' => __('An account with that email already exists.', 'caarmate'),
                'username_exists' => __('That username is already taken.', 'caarmate'),
                'weak_password' => __('Password must be at least 8 characters.', 'caarmate'),
                'invalid_email' => __('Please enter a valid email address.', 'caarmate'),
                'missing_fields' => __('Please fill in all required fields.', 'caarmate'),
                'create_failed' => __('Registration failed. Please try again.', 'caarmate'),
                'invalid_credentials' => __('Invalid username or password.', 'caarmate'),
                'nonce' => __('Security check failed. Please try again.', 'caarmate'),
            ];
            $msg = $errorMessages[$error] ?? __('Something went wrong. Please try again.', 'caarmate');
            $html .= '<div class="cm-auth-alert cm-auth-error">' . esc_html($msg) . '</div>';
        }

        if ($success) {
            $html .= '<div class="cm-auth-alert cm-auth-success">'
                . esc_html__('Registration successful! Please log in.', 'caarmate')
                . '</div>';
        }

        // Tab navigation
        $loginActive = $activeTab === 'login' ? 'cm-tab-active' : '';
        $registerActive = $activeTab === 'register' ? 'cm-tab-active' : '';

        $html .= '<div class="cm-auth-tabs">';
        $html .= '<a href="' . esc_url(home_url('/login/')) . '" class="cm-auth-tab ' . $loginActive . '">'
            . esc_html__('Log In', 'caarmate') . '</a>';
        $html .= '<a href="' . esc_url(home_url('/register/')) . '" class="cm-auth-tab ' . $registerActive . '">'
            . esc_html__('Register', 'caarmate') . '</a>';
        $html .= '</div>';

        // Forms
        if ($activeTab === 'login') {
            $html .= $this->renderLoginForm();
        } else {
            $html .= $this->renderRegisterForm();
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Render login form.
     *
     * @return string HTML.
     */
    private function renderLoginForm(): string
    {
        $nonce = wp_nonce_field('cm_login', '_cm_login_nonce', true, false);

        return '<form method="post" class="cm-auth-form">'
            . $nonce
            . '<input type="hidden" name="cm_auth_action" value="login">'
            . '<div class="cm-auth-field">'
            . '<label for="cm-login-email">' . esc_html__('Email or Username', 'caarmate') . '</label>'
            . '<input type="text" id="cm-login-email" name="cm_login_user" required '
            . 'placeholder="' . esc_attr__('you@example.com', 'caarmate') . '">'
            . '</div>'
            . '<div class="cm-auth-field">'
            . '<label for="cm-login-pass">' . esc_html__('Password', 'caarmate') . '</label>'
            . '<input type="password" id="cm-login-pass" name="cm_login_pass" required '
            . 'placeholder="' . esc_attr__('Your password', 'caarmate') . '">'
            . '</div>'
            . '<button type="submit" class="cm-btn cm-btn-primary cm-btn-full">'
            . esc_html__('Log In', 'caarmate')
            . '</button>'
            . '<p class="cm-auth-switch">'
            . esc_html__('Don\'t have an account?', 'caarmate') . ' '
            . '<a href="' . esc_url(home_url('/register/')) . '">'
            . esc_html__('Register here', 'caarmate') . '</a>'
            . '</p>'
            . '</form>';
    }

    /**
     * Render registration form with role selector.
     *
     * @return string HTML.
     */
    private function renderRegisterForm(): string
    {
        $nonce = wp_nonce_field('cm_register', '_cm_register_nonce', true, false);

        return '<form method="post" class="cm-auth-form">'
            . $nonce
            . '<input type="hidden" name="cm_auth_action" value="register">'
            . '<div class="cm-auth-field">'
            . '<label for="cm-reg-name">' . esc_html__('Full Name', 'caarmate') . '</label>'
            . '<input type="text" id="cm-reg-name" name="cm_reg_name" required '
            . 'placeholder="' . esc_attr__('John Doe', 'caarmate') . '">'
            . '</div>'
            . '<div class="cm-auth-field">'
            . '<label for="cm-reg-email">' . esc_html__('Email', 'caarmate') . '</label>'
            . '<input type="email" id="cm-reg-email" name="cm_reg_email" required '
            . 'placeholder="' . esc_attr__('you@example.com', 'caarmate') . '">'
            . '</div>'
            . '<div class="cm-auth-field">'
            . '<label for="cm-reg-pass">' . esc_html__('Password', 'caarmate') . '</label>'
            . '<input type="password" id="cm-reg-pass" name="cm_reg_pass" required '
            . 'minlength="8" placeholder="' . esc_attr__('Min. 8 characters', 'caarmate') . '">'
            . '</div>'
            . '<div class="cm-auth-field">'
            . '<label>' . esc_html__('I want to', 'caarmate') . '</label>'
            . '<div class="cm-role-selector">'
            . '<label class="cm-role-option">'
            . '<input type="radio" name="cm_reg_role" value="cm_passenger" checked>'
            . '<div class="cm-role-card">'
            . '<span class="cm-role-icon">🎫</span>'
            . '<span class="cm-role-title">' . esc_html__('Book Rides', 'caarmate') . '</span>'
            . '<span class="cm-role-desc">' . esc_html__('Find and book seats', 'caarmate') . '</span>'
            . '</div>'
            . '</label>'
            . '<label class="cm-role-option">'
            . '<input type="radio" name="cm_reg_role" value="cm_driver">'
            . '<div class="cm-role-card">'
            . '<span class="cm-role-icon">🚗</span>'
            . '<span class="cm-role-title">' . esc_html__('Offer Rides', 'caarmate') . '</span>'
            . '<span class="cm-role-desc">' . esc_html__('Share your journey', 'caarmate') . '</span>'
            . '</div>'
            . '</label>'
            . '</div>'
            . '</div>'
            . '<button type="submit" class="cm-btn cm-btn-primary cm-btn-full">'
            . esc_html__('Create Account', 'caarmate')
            . '</button>'
            . '<p class="cm-auth-switch">'
            . esc_html__('Already have an account?', 'caarmate') . ' '
            . '<a href="' . esc_url(home_url('/login/')) . '">'
            . esc_html__('Log in here', 'caarmate') . '</a>'
            . '</p>'
            . '</form>';
    }

    // =========================================================================
    //  POST Handlers
    // =========================================================================

    /**
     * Handle registration form submission.
     *
     * @return void
     */
    public function handleRegistration(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }
        if (!isset($_POST['cm_auth_action']) || $_POST['cm_auth_action'] !== 'register') {
            return;
        }

        // Nonce check
        if (
            !wp_verify_nonce(
                sanitize_text_field(wp_unslash($_POST['_cm_register_nonce'] ?? '')),
                'cm_register'
            )
        ) {
            $this->authRedirect('register', 'nonce');
            return;
        }

        $name = sanitize_text_field(wp_unslash($_POST['cm_reg_name'] ?? ''));
        $email = sanitize_email(wp_unslash($_POST['cm_reg_email'] ?? ''));
        $password = $_POST['cm_reg_pass'] ?? '';
        $role = sanitize_key($_POST['cm_reg_role'] ?? 'cm_passenger');

        // Validate
        if (empty($name) || empty($email) || empty($password)) {
            $this->authRedirect('register', 'missing_fields');
            return;
        }

        if (!is_email($email)) {
            $this->authRedirect('register', 'invalid_email');
            return;
        }

        if (strlen($password) < 8) {
            $this->authRedirect('register', 'weak_password');
            return;
        }

        if (email_exists($email)) {
            $this->authRedirect('register', 'email_exists');
            return;
        }

        // Only allow our custom roles.
        $allowedRoles = ['cm_passenger', 'cm_driver'];
        if (!in_array($role, $allowedRoles, true)) {
            $role = 'cm_passenger';
        }

        // Generate username from email.
        $username = sanitize_user(strstr($email, '@', true) ?: $email);
        if (username_exists($username)) {
            $username .= wp_rand(100, 9999);
        }

        // Create user.
        $userId = wp_create_user($username, $password, $email);

        if (is_wp_error($userId)) {
            $this->authRedirect('register', 'create_failed');
            return;
        }

        // Set display name & role.
        wp_update_user([
            'ID' => $userId,
            'display_name' => $name,
            'first_name' => $name,
            'role' => $role,
        ]);

        // Auto-login.
        wp_set_current_user($userId);
        wp_set_auth_cookie($userId, true);

        wp_safe_redirect(home_url('/dashboard/'));
        exit;
    }

    /**
     * Handle login form submission.
     *
     * @return void
     */
    public function handleLogin(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }
        if (!isset($_POST['cm_auth_action']) || $_POST['cm_auth_action'] !== 'login') {
            return;
        }

        // Nonce check
        if (
            !wp_verify_nonce(
                sanitize_text_field(wp_unslash($_POST['_cm_login_nonce'] ?? '')),
                'cm_login'
            )
        ) {
            $this->authRedirect('login', 'nonce');
            return;
        }

        $loginUser = sanitize_text_field(wp_unslash($_POST['cm_login_user'] ?? ''));
        $loginPass = $_POST['cm_login_pass'] ?? '';

        if (empty($loginUser) || empty($loginPass)) {
            $this->authRedirect('login', 'missing_fields');
            return;
        }

        $user = wp_signon([
            'user_login' => $loginUser,
            'user_password' => $loginPass,
            'remember' => true,
        ], is_ssl());

        if (is_wp_error($user)) {
            $this->authRedirect('login', 'invalid_credentials');
            return;
        }

        wp_safe_redirect(home_url('/dashboard/'));
        exit;
    }

    /**
     * Redirect back to auth page with error.
     *
     * @param string $tab   Active tab (login/register).
     * @param string $error Error code.
     * @return void
     */
    private function authRedirect(string $tab, string $error): void
    {
        $page = $tab === 'register' ? '/register/' : '/login/';
        $url = add_query_arg(
            ['tab' => $tab, 'auth_error' => sanitize_key($error)],
            home_url($page)
        );
        wp_safe_redirect(esc_url_raw($url));
        exit;
    }
}
