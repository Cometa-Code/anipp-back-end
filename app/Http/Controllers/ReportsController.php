<?php

namespace App\Http\Controllers;

use App\Http\Helpers\Responses;
use App\Http\Requests\Reports\CreateReportRequest;
use App\Models\Reports;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReportsController extends Controller
{
    public function store(CreateReportRequest $request)
    {
        $user = Auth::user();

        if ($user->role == 'associate') {
            return Responses::BADREQUEST('Apenas administradores podem tomar essa ação!');
        }

        if ($request->category < 0 || $request->category > 7) {
            return Responses::BADREQUEST('Categoria inválida!');
        }

        $data = $request->all();

        $createReport = Reports::create($data);

        if (!$createReport) {
            return Responses::BADREQUEST('Ocorreu um erro ao criar o informe!');
        }

        return Responses::CREATED('Informe criado com sucesso!');
    }

    public function index() {
        $reports = Reports::orderBy('id', 'DESC')
        ->get()
        ->groupBy('category')
        ->map(function ($items, $category) {
            return [
                'category' => $category,
                'data' => $items
            ];
        })
        ->values()
        ->toArray();

        return Responses::OK('', $reports);
    }

    public function delete($id)
    {
        $user = Auth::user();

        if ($user->role == 'associate') {
            return Responses::BADREQUEST('Apenas administradores podem tomar essa ação!');
        }

        $deleteReport = Reports::destroy($id);

        if (!$deleteReport) {
            return Responses::BADREQUEST('Ocorreu um erro ao tentar deletar um informativo!');
        }

        return Responses::OK('Informativo deletado com sucesso!');
    }
}
