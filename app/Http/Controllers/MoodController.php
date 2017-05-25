<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Mood;

class MoodController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function store()
    {
        $vote = json_decode(file_get_contents("php://input"), true);
        $mood = $vote["vote_mood"];
        $userToken = $vote["user_token"];
        $videoIndex = $vote["video_index"];

        // Handle case where the user is trying to vote for a video he already voted for
        $insert = DB::table('votes')->insertGetId([
            'user_token' => $userToken,
            'video_index' => $videoIndex,
            'vote_mood' => $mood
        ]);

        http_response_code(201); // Created
        echo json_encode($insert);
    }

    public function destroy($moodId)
    {
        $delete = DB::table('votes')->where(
            'vote_id', $moodId
        )->delete();

        http_response_code(200); // OK
        echo json_encode("Your vote has been deleted");
    }

    public function check(){
        $vote = json_decode(file_get_contents("php://input"), true);
        $userToken = $vote["user_token"];
        $videoIndex = $vote["video_index"];

        $result = DB::table('votes')->where([
            ['video_index', $videoIndex],
            ['user_token', $userToken]
        ])->get();

        if(sizeof($result) != 0){
            http_response_code(200);
            echo json_encode($result[0]);
        } else {
            http_response_code(204); // No contents
            echo json_encode(null);
        }
    }
}
