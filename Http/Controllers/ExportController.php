<?php

namespace Exports\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;

use Exports\Jobs\GenerateGeneralExport;
use Exports\Requests\ExportRequest;
use Exports\Models\{
    MaintenanceAgreement,
    Update,
    Comment,
    Customer,
    Order,
    Invoice,
    Item,
    Payment
};



class ExportController extends Controller
{
    public function __construct()
    {
        // Apply the jwt.auth middleware to all methods in this controller
        $this->middleware('jwt.auth');
    }

    /**
     * @param ExportRequest $request
     * @param string $method
     * @return JsonResponse
     */
    public function entityExport(ExportRequest $request, string $method = 'email'): JsonResponse
    {
        $entity = ucfirst($request->entity);

        $maps = [
            'invoice'               => Invoice::class,
            'comment'               => Comment::class,
            'customer'              => Customer::class,
            'item'                  => Item::class,
            'update'                => Update::class,
            'payment'               => Payment::class,
            'order'                 => Order::class,
            'maintenance-agreement' => MaintenanceAgreement::class
        ];

        $modelNamespace = $maps[strtolower($entity)];

        if (!class_exists($modelNamespace)) {
            return Response::json([
                'error' => true,
                'response' => null,
                'errors' => ["Entity not found."]
            ], 404);
        }

        $exportNamespace = '\\Exports\\Exporters\\' . ucfirst(str_plural(Str::camel($entity))) . 'Export';
        if (!class_exists($exportNamespace)) {
            return Response::json([
                'error' => true,
                'response' => null,
                'errors' => ["Entity export is not available."]
            ], 404);
        }

        dispatch(new GenerateGeneralExport(\Auth::user(), $request->all()));

        return Response::json([
            'error' => false,
            'email_sent' => true,
        ], 200);
    }
}
