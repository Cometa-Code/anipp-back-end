<?php

namespace App\Http\Controllers;

use App\Http\Helpers\Responses;
use App\Http\Requests\Users\CreateUserRequest;
use App\Http\Requests\Users\LoginUserRequest;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

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
}
