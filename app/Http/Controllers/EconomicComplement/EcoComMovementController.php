<?php

namespace App\Http\Controllers\EconomicComplement;

use App\Http\Controllers\Controller;
use App\Models\Devolution;
use App\Models\Due;
use App\Models\EconomicComplement\EcoComMovement;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PhpParser\Node\Expr\Cast\Object_;

class EcoComMovementController extends Controller
{
    public function index(Request $request,$affiliate_id)
    {
    }
}
