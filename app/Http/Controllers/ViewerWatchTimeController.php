<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;


class ViewerWatchTimeController extends Controller
{
    public function index()
    {
        //Fazendo um lista com os canais mais assistidos
        $channelsMostWatched = DB::table("watched_time as wt")
            ->join("channel as ch", "ch.id", "=", "wt.channel_id")
            ->select(DB::raw("SUM(wt.minutes) as timeWatched, ch.name"))
            ->groupBy('ch.id')
            ->orderBy('timeWatched', 'desc')
            ->get();

        //Ranking dos usuários que mais assistem
        $rankingViewers = DB::table("watched_time as wt")
            ->join("user as u", "u.id", "=", "wt.user_id")
            ->select(DB::raw("u.name, MAX(wt.minutes) as higherWatchedTime, SUM(wt.minutes) as timeWatched"))
            ->groupBy('u.id')
            ->orderBy('timeWatched', 'desc')
            ->get();

        //Pegando a data do do dia mais assistido de cada usuário
        $dateMostWatched =  DB::select(DB::raw("WITH cte AS (
            SELECT *, ROW_NUMBER() OVER (PARTITION BY user_id ORDER BY minutes DESC) rn FROM watched_time)
            SELECT id, user_id, channel_id, minutes, date FROM cte WHERE rn = 1 ORDER BY user_id ASC"));

        //Ajustando ranking dos usuários caso haja tempo assistido igual e adicionando o dia mais assistido de cada usuário
        $position = 1;
        for ($i = 0; $i < count($rankingViewers); $i++) {
            if ($rankingViewers[$i]->timeWatched == ($i < count($rankingViewers) - 1 ? $rankingViewers[$i + 1]->timeWatched : false)) {
                $rankingViewers[$i]->position = $position;
                $rankingViewers[$i + 1]->position = $position;
            } else {
                $rankingViewers[$i]->position = $position;
                $position++;
            }
            $rankingViewers[$i]->mostWatchedDay = $dateMostWatched[$i]->date;
        }

        $response = [
            'channelsMostWatched' => $channelsMostWatched,
            'rankingViewers' => $rankingViewers
        ];

        return response($response, 200, ['Content-Type' => 'application/json']);
    }
}
