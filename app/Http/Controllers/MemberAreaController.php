<?php

namespace App\Http\Controllers;

use App\Models\Socio;
use App\Services\MemberAreaSnapshotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MemberAreaController extends Controller
{
    public function show(Request $request, MemberAreaSnapshotService $snapshotService): JsonResponse
    {
        $socio = $request->attributes->get('member_socio');
        abort_unless($socio instanceof Socio, 401);

        return response()->json([
            'member' => [
                'number' => (int) $socio->num_socio,
                'name' => (string) $socio->nome,
            ],
            'data' => $snapshotService->buildForSocio($socio),
        ]);
    }
}
