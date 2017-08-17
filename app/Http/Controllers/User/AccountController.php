<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateAccountRequest;
use App\Repositories\Contracts\AccountRepository;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\Request;
use Mcamara\LaravelLocalization\LaravelLocalization;

class AccountController extends Controller
{
    /**
     * @var AccountRepository
     */
    protected $account;

    /**
     * @var \Mcamara\LaravelLocalization\LaravelLocalization
     */
    protected $localization;

    /**
     * RegisterController constructor.
     *
     * @param AccountRepository                                $account
     * @param \Mcamara\LaravelLocalization\LaravelLocalization $localization
     * @param \Illuminate\Contracts\View\Factory               $view
     *
     * @throws \Mcamara\LaravelLocalization\Exceptions\SupportedLocalesNotDefined
     */
    public function __construct(AccountRepository $account, LaravelLocalization $localization, Factory $view)
    {
        $this->account = $account;
        $this->localization = $localization;

        $view->composer('*', function (\Illuminate\View\View $view) {
            $locales = collect($this->localization->getSupportedLocales())->map(function ($item) {
                return $item['native'];
            });

            $view->withLocales($locales)->withTimezones(\DateTimeZone::listIdentifiers());
        });
    }

    /**
     * Show profile page.
     *
     * @param Request $request
     *
     * @throws \Mcamara\LaravelLocalization\Exceptions\SupportedLocalesNotDefined
     *
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        return view('user.account');
    }

    /**
     * @param \App\Http\Requests\UpdateAccountRequest $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateAccountRequest $request)
    {
        $request->headers->set('referer', route('user.account').'#edit');

        $this->account->update($request->input());

        return redirect()->route('user.account')
            ->withFlashSuccess(trans('labels.user.profile_updated'));
    }

    /**
     *  Send mail confirmation.
     */
    public function sendConfirmation()
    {
        $this->account->sendConfirmation();

        return redirect()->back()
            ->withFlashSuccess(trans('labels.user.email_confirmation_sended'));
    }

    /**
     *  Confirm email.
     *
     * @param $token
     *
     * @return
     */
    public function confirmEmail($token)
    {
        $this->account->confirmEmail($token);

        return redirect()->route('user.account')
            ->withFlashSuccess(trans('labels.user.email_confirmed'));
    }

    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function changePassword(Request $request)
    {
        $request->headers->set('referer', route('user.account').'#password');

        $this->validate($request, [
            'password' => 'required|min:6|confirmed',
        ]);

        $this->account->changePassword(
            $request->get('old_password'),
            $request->get('password')
        );

        return redirect()->route('user.account')
            ->withFlashSuccess(trans('labels.user.password_updated'));
    }

    /**
     * @param \Illuminate\Http\Request $request
     *
     * @throws \RuntimeException
     *
     * @return mixed
     */
    public function delete(Request $request)
    {
        $this->account->delete();

        auth()->logout();
        $request->session()->flush();
        $request->session()->regenerate();

        return redirect()->route('home')
            ->withFlashSuccess(trans('labels.user.account_deleted'));
    }
}
