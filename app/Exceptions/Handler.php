<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Validation\ValidationException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }
    /**
	 * Report or log an exception.
	 *
	 * @param  \Throwable  $exception
	 * @return void
	 */
	public function report(Throwable $exception)
	{
		parent::report($exception);
	}
    /**
	 * Render an exception into an HTTP response.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \Throwable  $exception
	 * @return \Illuminate\Http\Response
	 */
	public function render($request, Throwable $exception)
	{
        $message = $exception->getMessage();
        if (empty($message)) {
            $message = null;
        }

        if ($exception instanceof AuthenticationException) {
            return response()->json([
                'message' => 'Unauthenticated',
                'errors' => [
                    'type' => ['No autorizado para el recurso'],
                ]
            ], 401);
        }elseif ($exception instanceof ModelNotFoundException or $exception instanceof NotFoundHttpException) {
            preg_match('/[^ ]*$/', $message, $id);
            if (count($id) > 0) {
                $id = $id[0];
                preg_match('#\[(.*?)\]#', $message, $model);
                if (count($model) > 1) {
                    $message = $model[1];
                    $message = ': ' . $message . ' id: ' . $id;
                } else {
                    $message = '';
                }
            } else {
                $message = '';
            }
			return response()->json([
				'message' => 'Data not found',
				'errors' => [
					'type' => ['Recurso no encontrado' . $message],
				]
			], 404);
		}elseif ($exception instanceof HttpException) {
			switch ($exception->getStatusCode()) {
				case 401:
					return response()->json([
						'message' => 'Unauthorized',
						'exception' => $exception,
						'errors' => [
							'type' => [$message ?? 'Sesión caducada'],
						]
					], $exception->getStatusCode());
				case 403:
					return response()->json([
						'message' => 'Forbidden',
						'errors' => [
							'type' => [$message ?? 'No autorizado'],
						]
					], $exception->getStatusCode());
					break;
				case 409:
					return response()->json([
						'message' => 'Conflict',
						'errors' => [
							'type' => [$message ?? 'El registro ya existe'],
						]
					], $exception->getStatusCode());
					break;
				case 500:
					return response()->json([
						'message' => 'Internal Server Error',
						'exception' => $exception,
						'errors' => [
							'type' => [$message ?? 'Error interno del servidor'],
						]
					], $exception->getStatusCode());
					break;
				default:
					return response()->json([
						'message' => 'Internal Server Error',
						'exception' => $exception,
						'errors' => [
							'type' => [$message ?? 'Error ' . $exception->getStatusCode()],
						]
					], $exception->getStatusCode());
			}
		} elseif ($exception instanceof ValidationException) {
			return response()->json([
				'message' => 'Verifique datos',
				'errors' => $exception->errors()
			], 409);
		} else {
			\Log::channel('error')->error('Error inesperado: ' . $exception);
		}
        return parent::render($request, $exception);
    }
}
