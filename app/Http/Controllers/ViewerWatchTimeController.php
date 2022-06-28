<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\WatchTime;
use Illuminate\Support\Facades\DB;


class ViewerWatchTimeController extends Controller
{
    public function index()
    {
        //Fazendo um lista com os canais mais assistidos
        $channelsMostWatched = DB::table("watched_time as wt")
            ->join("channel as ch", "ch.id", "=", "wt.channel_id")
            ->select(DB::raw("SUM(wt.minutes) as TimeWatched, ch.name"))
            ->groupBy('ch.id')
            ->get();

        //Ranking dos usuários que mais assistem
        $rankingViewersObject = DB::table("watched_time as wt")
            ->join("user as u", "u.id", "=", "wt.user_id")
            ->select(DB::raw("u.name, MAX(wt.minutes) as higherWatchedTime, wt.date"))
            ->groupBy('u.id')
            ->get();

        //Pegando a data do do dia mais assistido de cada usuário
        $dateMostWatched =  DB::select(DB::raw("WITH cte AS (
            SELECT *, ROW_NUMBER() OVER (PARTITION BY user_id ORDER BY minutes DESC) rn FROM watched_time)
            SELECT id, user_id, channel_id, minutes, date FROM cte WHERE rn = 1 ORDER BY user_id ASC"));

        // // Transforma o objeto em array associativo e depois adiciona a posição do usuário no ranking
        $rankingViewers = json_decode(json_encode($rankingViewersObject), true);
        foreach ($rankingViewersObject as $key => $viewer) {
            $rankingViewers[$key]['position'] = intval($key) + 1;
            $rankingViewers[$key]['mostWatchedDay'] = $dateMostWatched[$key]->date;
        }

        $response = [
            'channelsMostWatched' => $channelsMostWatched,
            'rankingViewers' => $rankingViewers
        ];

        return response($response, 200, ['Content-Type' => 'application/json']);
    }
}
