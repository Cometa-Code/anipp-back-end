<?php

namespace App\Http\Controllers;

use Exception;
use App\Http\Helpers\Responses;
use App\Http\Requests\Users\CreateAdvancedUserRequest;
use App\Http\Requests\Users\CreateUserRequest;
use App\Http\Requests\Users\GenerateRecoverPasswordTokenUserRequest;
use App\Http\Requests\Users\LoginUserRequest;
use App\Http\Requests\Users\RecoverPasswordUserRequest;
use App\Mail\PasswordReset;
use App\Models\ResetPassword;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class UserController extends Controller
{
    public function get_associates(Request $request) {
        $user = Auth::user();

        $items_per_page = $request->query('items_per_page', 10);
        $termsFilter = $request->query('terms_filter', '');

        if ($user->role == 'associate') return Responses::BADREQUEST('Usuário não possui permissões suficientes!');

        if ($user->role == 'superadmin') {
            $users = User::where(function($query) use ($termsFilter) {
                        $query->where('name', 'LIKE', "%$termsFilter%")
                            ->orWhere('email', 'LIKE', "%$termsFilter%")
                            ->orWhere('document_cpf', 'LIKE', "%$termsFilter%");
                        })
                        ->where('email', '!=', $user->email)
                        ->orderBy('name', 'ASC')
                        ->paginate($items_per_page);
        }

        if ($user->role == 'admin') {
            $users = User::where(function($query) use ($termsFilter) {
                $query->where('name', 'LIKE', "%$termsFilter%")
                    ->orWhere('email', 'LIKE', "%$termsFilter%")
                    ->orWhere('document_cpf', 'LIKE', "%$termsFilter%");
                })
                ->where('email', '!=', $user->email)
                ->where('role', '!=', 'superadmin')
                ->orderBy('name', 'ASC')
                ->paginate($items_per_page);
        }

        return Responses::OK('Associados encontrados com sucesso', $users);
    }

    public function create_advanced_user(CreateAdvancedUserRequest $request) {
        $user = Auth::user();

        if ($user->role == 'associate') {
            return Responses::BADREQUEST('Apenas administradores podem tomar essa ação!');
        }

        if (($request->role == 'superadmin' || $request->role == 'admin') && $user->role == 'admin') {
            return Responses::BADREQUEST('Apenas administradores autorizados podem tomar essa ação!');
        }

        $hasUser = User::where('email', $request->email)->orWhere('document_cpf', $request->document_cpf)->first();

        if ($hasUser) {
            return Responses::BADREQUEST('Usuário já cadastrado com o CPF ou E-mail informados!');
        }

        $data = $request->all();

        $data['bank_identifier_a'] = "000" . $data['document_cpf'];

        $createUser = User::create($data);

        if (!$createUser) {
            return Responses::BADREQUEST('Erro ao criar usuário!');
        }

        return Responses::CREATED('Usuário adicionado com sucesso!');
    }

    public function update_advanced_user(Request $request, $document) {
        $user = Auth::user();

        if ($user->role == 'associate') {
            return Responses::BADREQUEST('Apenas administradores podem tomar essa ação!');
        }

        $getUser = User::where('document_cpf', $document)->first();

        if (!$getUser) {
            return Responses::BADREQUEST('Não foi possível encontrar um usuário com os dados especificados!');
        }

        $hasUserEmail = User::where('email', $request->email)->first();

        if (($request->email != $getUser->email) && $hasUserEmail) {
            return Responses::BADREQUEST('E-mail já cadastrado na base de dados.');
        }

        if ($request->role) {
            if (($request->role == 'superadmin' || $request->role == 'admin') && $user->role == 'admin') {
                return Responses::BADREQUEST('Apenas administradores autorizados podem tomar essa ação!');
            }
        }

        $data = $request->all();

        $data['document_cpf'] = $document;

        $getUser->update($data);

        if (!$getUser) {
            return Responses::BADREQUEST('Erro ao atualizar usuário!');
        }

        return Responses::OK('Usuário atualizado com sucesso!');
    }

    public function user()
    {
        $user = Auth::user();

        return $user;
    }

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
        $data['bank_identifier_a'] = "000" . $data['document_cpf'];

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

    public function update_user(Request $request) {
        $user = Auth::user();

        $data = $request->all();

        $updateUser = $user->update($data);

        if (!$updateUser) {
            return Responses::BADREQUEST('Erro ao atualizar dados do usuário!');
        }

        return Responses::OK('Dados atualizados com sucesso!');
    }

    public function update_password(Request $request) {
        $validated = $request->validate([
            'actual_password' => 'required|string',
            'new_password' => 'required|string'
        ]);

        $user = Auth::user();

        if (!Hash::check($request->actual_password, $user->password)) {
            return Responses::BADREQUEST('Credenciais inválidas!');
        }

        $newPasswordHash = Hash::make($request->new_password);

        $user->update(['password' => $newPasswordHash]);

        return Responses::OK('Credenciais atualizadas com sucesso!');
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

    public function deactivate_user($id)
    {
        $user = Auth::user();

        if ($user->role == 'associate') {
            return Responses::BADREQUEST('Apenas administradores podem tomar essa ação!');
        }

        $getUser = User::where('id', $id)->first();

        if (!$getUser) {
            return Responses::BADREQUEST('Usuário não encontrado!');
        }

        $getUser = $getUser->delete();

        return Responses::OK('Usuário deletado com sucesso!');
    }
}
