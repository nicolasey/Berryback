<?php
/**
 * Created by PhpStorm.
 * User: AngelZatch
 * Date: 26/02/2017
 * Time: 16:35
 */

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\User;

class UserController extends BaseController
{
	use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

	/**
	 * Create a new controller instance.
	 *
	 * @return void
	 */
	/*public function __construct()
	{
		$this->middleware('auth');
	}*/

	// GET a user profile
	public function show($token)
	{
		$stmt = "SELECT
					user_pseudo AS pseudo,
                    user_pp AS picture
                    FROM user
                    WHERE user_token = :token";

        $details = DB::select($stmt, ['token' => $token]);

        return json_encode($details[0]);
    }

	// GET boxes of the user
	public function boxes($token)
	{
		$stmt = "SELECT
					box_token
                    room_name,
                    creation_date,
                    room_type,
                    room_type,
                    room_lang,
                    room_description
                    FROM rooms
                    WHERE room_creator = :token";
		$boxes = DB::select($stmt, ['token' => $token]);

		return json_encode($boxes);
	}

	public function stats($token)
	{
		$stmt = "SELECT
					stat_rooms_created AS boxes,
					stat_songs_submitted AS submissions,
					stat_visitors AS visitors,
					stat_followers AS followers
					FROM user_stats
					WHERE user_token = :token";

		$stats = DB::select($stmt, ['token' => $token]);

		return json_encode($stats[0]);
	}

	public function likes($token)
	{
		$table = "votes";
		$likes = DB::table($table)
			->join('song_base', $table.'.video_index', '=', 'song_base.song_base_id')
			->select('video_index', 'link', 'video_name', 'vote_mood')
			->where('user_token', $token)
			->orderBy('vote_mood', 'asc')
			->get();
		
		http_response_code(200);
		return json_encode($likes);
	}

	public function create()
	{

	}

	public function destroy()
	{

	}

	public function update()
	{

	}

}