<?php

namespace App\Http\Controllers;

use App\Services\RexlCloud\ApiException;
use App\Services\RexlCloud\RexlCloudClient;
use App\Services\RexlCloud\UnauthenticatedException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Passes a template file from the cloud on to the browser without loading it into memory.
 */
class TemplateDownloadController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $data = $request->validate([
            'group' => 'required|string',
            'variant' => 'required|string',
            'path' => 'required|string',
        ]);

        try {
            $response = RexlCloudClient::fromSession()->downloadTemplateFile($data['group'], $data['variant'], $data['path']);
        } catch (UnauthenticatedException) {
            return redirect()->route('login');
        } catch (ApiException $e) {
            abort($e->getCode() === 404 ? 404 : 502, $e->getMessage());
        }

        $body = $response->toPsrResponse()->getBody();
        $name = basename(str_replace('\\', '/', $data['path']));

        return new StreamedResponse(function () use ($body) {
            while (! $body->eof()) {
                echo $body->read(65536);
                flush();
            }
        }, 200, array_filter([
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="'.str_replace('"', '', $name).'"',
            'Content-Length' => $response->header('Content-Length') ?: null,
        ]));
    }
}
