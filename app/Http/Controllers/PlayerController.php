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
		$link = json_decode(file_get_contents("php://input"), true);

		$stmt = "SELECT * FROM song_base WHERE link = :link";

		$base = DB::select($stmt, ['link' => $link]);

		if(count($base) == 0){
			try{
				$contents = file_get_contents("http://youtube.com/get_video_info?video_id=".$link);
				parse_str($contents, $ytarr);
				$title = addslashes($ytarr['title']);
				$pending = 0;
				if($title == ""){
					$title = "-";
					$pending = 1;
				}
				$this->insertToBase(["link" => $link, "title" => $title, "pending" => $pending]);
			} catch(\PDOException $e){
				$title = "-";
				$pending = 1;
			}
		} else {
			$pending = $base[0]["pending"];
		}

		$order = $this->getLastOrder($box);

		$this->insertToBox(["index" => $link, "order" => $order, "user" => "D1JU70"]);

		echo json_encode("Video submitted successfully");
		http_response_code(201);

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

	private function insertToBase(Array $video){
		$stmt = "INSERT INTO song_base(link, video_name, pending)
					VALUES(:link, :title, :pending)";

		$insert = DB::select($stmt, [
			"link" => $video["link"],
			"title" => $video["title"],
			"pending" => $video["pending"]
		]);

		return $insert;
	}

	private function getLastOrder($token){
		$stmt = "SELECT playlist_order FROM roomHistory_$token
					ORDER BY playlist_order DESC LIMIT 1";

		$order = DB::select($stmt);

		return ($order != null)?$order[0]++:1;
	}

	private function insertToBox(Array $video){
		$stmt = "INSERT INTO roomhistory_$box(video_index, playlist_order, history_time, history_user)
					VALUES(:index, :order, :time, :user)";

		$now = new \DateTime();

		$insert = DB::select($stmt, [
			':index' => $video["index"],
			':order' => $video["order"],
			':time' => $now,
			':user' => 'D1JU70'
		]);
	}
}