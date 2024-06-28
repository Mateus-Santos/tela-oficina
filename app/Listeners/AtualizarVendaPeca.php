<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Events\VendaPeca;


class AtualizarVendaPeca
{

    public function __construct()
    {
        //
    }

    public function handle(VendaPeca $event)
    {
        $peca = $event->pecavenda->peca;
        $peca->quantidade -= $event->pecavenda->quantidade;
        $peca->save();
    }
}
