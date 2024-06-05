<?php

namespace App\Http\Controllers;

use App\Http\Helpers\Responses;
use App\Models\UserDependents;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserDependentsController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $getDependents = UserDependents::where('responsible_user_id', $user->id)->get();

        return Responses::OK('', $getDependents);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'email' => 'required|string',
        ]);

        $user = Auth::user();

        $createDependent = UserDependents::create([
            'responsible_user_id' => $user->id,
            'name' => $request->name,
            'phone' => $request->phone,
            'email' => $request->email,
            'degree_of_kinship' => $request->degree_of_kinship,
        ]);

        if (!$createDependent) {
            return Responses::BADREQUEST('Ocorreu um erro durante a criação do dependente');
        }

        return Responses::CREATED('Dependente adicionado com sucesso!');
    }

    public function destroy($id)
    {
        $user = Auth::user();

        $getUserDependent = UserDependents::where('id', $id)->first();

        if (!$getUserDependent || ($user->role == 'associate' && $getUserDependent->responsible_user_id != $user->id)) {
            return Responses::BADREQUEST('Dependente não localizado!');
        }

        $getUserDependent->delete();

        return Responses::OK('Dependente removido com sucesso!');
    }
}
