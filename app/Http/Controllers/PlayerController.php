<?php
/**
 * Created by PhpStorm.
 * User: AngelZatch
 * Date: 03/04/2017
 * Time: 21:47
 */

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Box;

class PlayerController extends BaseController
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

	// Fetches playlist for a box
	public function listing($box){
		$stmt = "SELECT
                    room_history_id,
                    playlist_order,
                    video_index,
                    link,
                    video_name
                    history_user,
                    history_time,
                    video_status,
                    pending,
                    user_pseudo
                    FROM roomHistory_$box rh
                    JOIN song_base sb ON rh.video_index = sb.song_base_id
                    LEFT JOIN user u ON rh.history_user = u.user_token
                    ORDER BY playlist_order DESC";
		$playlist = DB::select($stmt);

		return json_encode($playlist);
	}

	// POST a video
	public function store($box){

	}

	// GET the current playing video
	public function current($box){
		$stmt = "SELECT video_index,
					history_start,
					link,
					video_name,
					user_pseudo
					FROM roomhistory_$box rh
					JOIN song_base sb ON rh.video_index = sb.song_base_id
					JOIN user u ON rh.history_user = u.user_token
					WHERE video_status = 1
					ORDER BY playlist_order DESC
					LIMIT 1";

		$current = DB::select($stmt);

		return json_encode($current[0]);
	}
}