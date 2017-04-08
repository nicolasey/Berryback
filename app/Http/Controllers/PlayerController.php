<?php
/**
 * Created by PhpStorm.
 * User: AngelZatch
 * Date: 03/04/2017
 * Time: 21:47
 */

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;

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
	public function listing($boxToken){
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
                    FROM roomHistory_$boxToken rh
                    JOIN song_base sb ON rh.video_index = sb.song_base_id
                    LEFT JOIN user u ON rh.history_user = u.user_token
                    ORDER BY playlist_order DESC";
		$playlist = DB::select($stmt);

		return json_encode($playlist);
	}

	// POST a video
	public function store($boxToken){
		$video = json_decode(file_get_contents("php://input"), true);
		$link = $video["link"];
		$author = $video["author"];

		$base = DB::table('song_base')
			->where('link', $link)
			->first();

		if(count($base) == 0){
			try{
				$contents = file_get_contents("http://youtube.com/get_video_info?video_id=".$link);
				parse_str($contents, $youtubeData);
				$title = addslashes($youtubeData['title']);
				$duration = $youtubeData['length_seconds'];
				$pending = 0;
				if($title == ""){
					$title = "-";
					$pending = 1;
				}
			} catch(\PDOException $e){
				$title = "-";
				$pending = 1;
			}
			$index = $this->insertToBase(["link" => $link, "title" => $title, "pending" => $pending, "duration" => $duration]);
		} else {
			$index = $base->song_base_id;
		}

		$order = $this->getLastOrder($boxToken);

		$insertStatus = $this->insertToBox($boxToken, ["index" => $index, "order" => $order, "author" => $author]);

		if($insertStatus){
			echo json_encode("Video submitted successfully.");
			http_response_code(201);
		} else {
			echo json_encode("An error occurred. Please try again.");
			http_response_code(500);
		}

	}

	// GET the current playing video
	public function current($boxToken){
		$stmt = "SELECT video_index,
					history_start,
					link,
					video_name,
					user_pseudo
					FROM roomhistory_$boxToken rh
					JOIN song_base sb ON rh.video_index = sb.song_base_id
					JOIN user u ON rh.history_user = u.user_token
					WHERE video_status = 1
					ORDER BY playlist_order DESC
					LIMIT 1";

		$current = DB::select($stmt);

		return json_encode($current[0]);
	}

	private function insertToBase(Array $video){
		$id = DB::table('song_base')->insertGetId([
			'link' => $video["link"],
			'video_name' => $video["title"],
			'pending' => $video["pending"],
			'duration' => $video["duration"]
		], 'song_base_id');

		return $id;
	}

	private function getLastOrder($boxToken){
		$order = DB::table('roomHistory_'.$boxToken)
			->latest('playlist_order')
			->pluck('playlist_order')
			->first();

		return ($order != null) ? ++$order : 1;
	}

	private function insertToBox($boxToken, Array $video){
		$now = new \DateTime();

		$insert = DB::table('roomHistory_'.$boxToken)->insert([
			'video_index' => $video["index"],
			'playlist_order' => $video["order"],
			'history_time' => $now,
			'history_user' => $video["author"]
		]);
		return $insert;
	}
}