<?php

    namespace App\Http\Controllers\Auth;

    use App\Http\Controllers\Controller;
    use App\User;
    use Illuminate\Foundation\Auth\AuthenticatesUsers;
    use Illuminate\Http\Request;

    /**
     * Class LoginController
     *
     * @package App\Http\Controllers\Auth
     */
    class LoginController extends Controller {
        /*
        |--------------------------------------------------------------------------
        | Login Controller
        |--------------------------------------------------------------------------
        |
        | This controller handles authenticating users for the application and
        | redirecting them to your home screen. The controller uses a trait
        | to conveniently provide its functionality to your applications.
        |
        */

        use AuthenticatesUsers;

        /**
         * Where to redirect users after login.
         *
         * @var string
         */
        protected $redirectTo = '/';

        /**
         * Create a new controller instance.
         *
         */
        public function __construct() {
            $this->redirectTo = route('user/gallery');
            $this->middleware('guest', ['except' => 'checkLogoutToken']);
        }


        /**
         * Handle a login request to the application.
         *
         * @param  \Illuminate\Http\Request $request
         *
         * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
         */
        public function login(Request $request) {
            $this->validateLogin($request);
            if ($this->hasTooManyLoginAttempts($request)) {
                $this->fireLockoutEvent($request);

                return $this->sendLockoutResponse($request);
            }
            if ($this->attemptLogin($request)) {
                if ($this->guard()->user()->isActive()) {
                    return $this->sendLoginResponse($request);
                } else {
                    $this->guard()->logout();
                    return $this->sendInactiveAccountResponse($request);
                }
            }
            $this->incrementLoginAttempts($request);

            return $this->sendFailedLoginResponse($request);
        }


        /**
         * Get the failed login response instance.
         *
         * @param  \Illuminate\Http\Request $request
         *
         * @return \Illuminate\Http\RedirectResponse
         */
        protected function sendFailedLoginResponse(Request $request) {
            if ($request->ajax()) {
                return response(['success' => false, 'error' => trans('auth.failed')], 403);
            } else {
                return redirect()->back()
                    ->withInput($request->only($this->username(), 'remember'))
                    ->withErrors([
                        $this->username() => trans('auth.failed'),
                    ]);
            }
        }

        /**
         * Redirect the user after determining they are locked out.
         *
         * @param  \Illuminate\Http\Request $request
         *
         * @return \Illuminate\Http\RedirectResponse
         */
        protected function sendLockoutResponse(Request $request) {
            $seconds = $this->limiter()->availableIn(
                $this->throttleKey($request)
            );

            $message = trans('auth.throttle', ['seconds' => $seconds]);

            if ($request->ajax()) {
                return response(['success' => false, 'error' => $message], 403);
            } else {
                return redirect()->back()
                    ->withInput($request->only($this->username(), 'remember'))
                    ->withErrors([$this->username() => $message]);
            }
        }

        /**
         * Get the needed authorization credentials from the request.
         *
         * @param  \Illuminate\Http\Request $request
         *
         * @return array
         */
        protected function credentials(Request $request) {
            if (filter_var($request->input($this->username()), FILTER_VALIDATE_EMAIL)) {
                $request->merge(['email' => $request->input($this->username())]);
                return $request->only('email', 'password');
            }
            return $request->only($this->username(), 'password');
        }

        /**
         * @param Request $request
         * @param         $token
         *
         * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
         */
        public function checkLogoutToken(Request $request, $token) {
            if ($token != $request->session()->token()) {
                return redirect()->back(302, [], route('home'))->withErrors(['logout' => 'token_invalid']);
            }
            return $this->logout($request);
        }

        /**
         * Log the user out of the application.
         *
         * @param \Illuminate\Http\Request $request
         *
         * @return \Illuminate\Http\Response
         */
        public function logout(Request $request) {
            $this->guard()->logout();

            $request->session()->flush();

            $request->session()->regenerate();

            return redirect(route('home'));
        }

        /**
         * The user has been authenticated.
         *
         * @param  \Illuminate\Http\Request $request
         * @param  User                    $user
         *
         * @return mixed
         */
        protected function authenticated(Request $request, User $user) {
            $user->last_login = time();
            $user->save();
            if ($request->ajax()) {
                return response(['success' => true, 'redirect' => redirect()->intended($this->redirectPath())->getTargetUrl()], 200);
            }
            return null;
        }

        /**
         * @return string
         */
        public function username() {
            return 'username';
        }

        /**
         * @param $request
         *
         * @return LoginController|\Illuminate\Http\RedirectResponse
         */
        protected function sendInactiveAccountResponse(Request $request) {
            if ($request->ajax()) {
                return response(['success' => false, 'error' => trans('auth.account_inactive')], 403);
            } else {
                return redirect()->back()
                    ->withInput($request->only($this->username(), 'remember'))
                    ->withErrors([
                        $this->username() => trans('auth.account_inactive'),
                    ]);
            }
        }
    }
