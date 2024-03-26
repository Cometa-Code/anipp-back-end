<?php

namespace App\Http\Controllers;

use Exception;
use App\Http\Helpers\Responses;
use App\Http\Requests\Users\CreateUserRequest;
use App\Http\Requests\Users\GenerateRecoverPasswordTokenUserRequest;
use App\Http\Requests\Users\LoginUserRequest;
use App\Http\Requests\Users\RecoverPasswordUserRequest;
use App\Mail\PasswordReset;
use App\Models\ResetPassword;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class UserController extends Controller
{
    public function login(LoginUserRequest $request)
    {
        $hasUser = User::where('document_cpf', $request->user)->first();

        if (!$hasUser) {
            return Responses::BADREQUEST('Usuário ou senha incorretos!');
        }

        if (!Hash::check($request->password, $hasUser->password)) {
            return Responses::BADREQUEST('Usuário ou senha incorretos!');
        }

        $token = $hasUser->createToken('auth_token')->plainTextToken;

        return Responses::OK('Usuário autenticado com sucesso!', [
            'token' => $token
        ]);
    }

    public function store(CreateUserRequest $request)
    {
        $hasUser = User::where('email', $request->email)->orWhere('document_cpf', $request->document_cpf)->first();

        if ($hasUser) {
            return Responses::BADREQUEST('E-mail ou CPF já cadastrados na base de dados!');
        }

        $data = $request->all();

        $data['role'] = 'associate';
        $data['is_associate'] = true;

        $createUser = User::create($data);

        if (!$createUser) {
            return Responses::BADREQUEST('Erro ao criar usuário');
        }

        return Responses::CREATED('Usuário criado com sucesso!', $createUser);
    }

    public function generate_token(GenerateRecoverPasswordTokenUserRequest $request)
    {
        $user = User::where('document_cpf', $request->user)->first();

        if (!$user) {
            return Responses::BADREQUEST('Usuário solicitou um token nos últimos 10 minutos ou é inválido!');
        }

        $hasRecentToken = ResetPassword::where('user_id', $user->id)
            ->where('token_expired_at', '>', Carbon::now())
            ->orderBy('created_at', 'desc')
            ->first();

        if ($hasRecentToken) {
            return Responses::BADREQUEST('Usuário solicitou um token nos últimos 10 minutos ou é inválido!');
        }

        $timestamp = time();
        $random_token = md5("$user->id$timestamp");

        $createToken = ResetPassword::create([
            'user_id' => $user->id,
            'token' => $random_token,
            'token_expired_at' => Carbon::now()->addMinutes(10)
        ]);

        if (!$createToken) {
            return Responses::BADREQUEST('Ocorreu um erro ao tentar criar um token para esse usuário');
        }

        $sendMail = Mail::to($user->email)->send(new PasswordReset($user->name, $user->email, $random_token));

        return Responses::CREATED('Token enviado ao e-mail do usuário!');
    }

    public function verify_token($token)
    {
        $hasToken = ResetPassword::where('token', $token)
            ->where('token_expired_at', '>', Carbon::now())
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$hasToken) {
            return Responses::NOTFOUND('Token inválido, expirado ou já utilizado!');
        }

        return Responses::OK('Token válido!');
    }

    public function recover_password(RecoverPasswordUserRequest $request)
    {
        $hasToken = ResetPassword::where('token', $request->token)
            ->where('token_expired_at', '>', Carbon::now())
            ->whereHas('user', function ($query) use ($request) {
                $query->where('email', $request->email);
            })
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$hasToken) {
            return Responses::NOTFOUND('Token inválido, expirado ou já utilizado!');
        }

        $user = User::firstWhere('email', $request->email);

        $user->update([
            'password' => $request->new_password
        ]);

        $hasToken->delete();

        return Responses::OK('Senha redefinida com sucesso!');
    }
}
