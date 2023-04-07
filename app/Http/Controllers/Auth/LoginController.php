<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\UserStoreRequest;
use App\Models\User;
use App\Models\UserVerify;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Psy\Util\Str;


class LoginController extends Controller
{
    public function showLogin()
    {
        return view("auth.login");
    }

    public function showRegister()
    {
        return view("front.auth.register");
    }

    public function login(LoginRequest $request)
    {

        $email = $request->email;
        $password = $request->password;
        $remember = $request->remember;

        !is_null($remember) ? $remember = true : $remember = false;

        $user = User::where("email", $email)->first();

        if ($user && Hash::check($password, $user->password)) {

            Auth::login($user, $remember);
            return redirect()->route("admin.index");
        } else {
            return redirect()
                ->route('login')
                ->withErrors([
                    'email' => "Verdiğiniz bilgilerle eşleşen bir kullanıcı bulunamadı."
                ])
                ->onlyInput("email", "remember");

        }
    }

    public function logout(Request $request)
    {
        if (Auth::check())
        {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route("login");

        }
    }

    public function register(UserStoreRequest $request)
    {
        $user = new User();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->username = $request->username;
        $user->password = bcrypt($request->password);

        $user->status = 0;
        $user->save();

        $token = \Illuminate\Support\Str::random("60");
        UserVerify::create([
            "user_id" => $user->id,
            "token" => $token
        ]);
        Mail::send("email.verify",compact("token"),function ($mail) use ($user){
            $mail->to($user->email);
            $mail->subject("Doğrulama Emaili");
        });

        alert()
            ->success('Başarılı', "Mailinizi onaylamanız için onay maili gönderilmiştir. Lütfen mail kutunuzu kontrol ediniz.")
            ->showConfirmButton('Tamam', '#3085d6')
            ->autoClose(5000);
        return redirect()->back();
    }

    public function verify(Request $request, $token)
    {
        $verifyQuery = UserVerify::query()->with("user")->where("token", $token);
        $find = $verifyQuery->first();
        if (!is_null($find))
        {
            $user = $find->user;

            if (is_null($user->email_verified_at))
            {
                $user->email_verified_at = now();
                $user->status = 1;
                $user->save();
                //$this->log("verify user", $user->id, $user->toArray(), User::class);

                $verifyQuery->delete();
                $message = "Emailiniz doğrulandı.";
            }
            else
            {
                $message = "Emailiniz daha önce doğrulanmıştı. Giriş yapabilirsiniz.";
            }
            alert()
                ->success('Başarılı', $message)
                ->showConfirmButton('Tamam', '#3085d6')
                ->autoClose(5000);

            return redirect()->route("login");
        }
        else
        {
            abort(404);
        }
    }
}
