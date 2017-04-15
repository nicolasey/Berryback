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
		$table = 'roomHistory_'.$boxToken;

		$listing = DB::table($table)
			->join('song_base', $table.'.video_index', '=', 'song_base.song_base_id')
			->leftJoin('user', $table.'.history_user', '=', 'user.user_token')
			->select('room_history_id', 'playlist_order', 'video_index', 'link',
				'video_name', 'history_user', 'history_time', 'video_status',
				'pending', 'user_pseudo')
			->orderBy('playlist_order', 'desc')
			->get();

		http_response_code(200);
		return json_encode($listing);
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
				$title = (isset($youtubeData['title']))?$youtubeData['title']:"-";
				$duration = (isset($youtubeData['length_seconds']))?$youtubeData['length_seconds']:NULL;
				$pending = (isset($youtubeData['title']))?0:1;
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
		$table = 'roomHistory_'.$boxToken;

		$current = DB::table($table)
			->join('song_base', $table.'.video_index', '=', 'song_base.song_base_id')
			->join('user', $table.'.history_user', '=', 'user.user_token')
			->join('user_preferences', $table.'.history_user', '=', 'user_preferences.user_token')
			->select('video_index', 'history_start', 'link', 'video_name', 'user_pseudo', 'up_color')
			->where('video_status', 1)
			->orderBy('playlist_order')
			->first();

		http_response_code(200);
		if(!$current) {
			$current = $this->next($boxToken);
			return $current;
		} else {
			return json_encode($current);
		}
	}

	// GET the next video in the playlist
	public function next($boxToken){
		$table = 'roomHistory_'.$boxToken;

		$next = DB::table($table)
			->join('song_base', $table.'.video_index', '=', 'song_base.song_base_id')
			->join('user', $table.'.history_user', '=', 'user.user_token')
			->join('user_preferences', $table.'.history_user', '=', 'user_preferences.user_token')
			->select('video_index', 'history_user', 'link', 'video_name', 'playlist_order', 'user_pseudo', 'up_color')
			->where('video_status', 0)
			->orderBy('playlist_order', 'asc')
			->first();

		// We skip over ignored videos and put them all to 2 as well as the one that just ended
		if($next){
			DB::table('roomHistory_'.$boxToken)
				->where([
					['playlist_order', '<', $next->playlist_order],
					['video_status', '!=', 2]
				])
				->update(['video_status' => 2]);
			$this->playing($boxToken, $next->playlist_order);

			http_response_code(200);
			return json_encode($next);
		} else {
			http_response_code(200);
			return json_encode(false);
		}
	}

	// UPDATE video in the playlist
	public function update($boxToken){
		$video = json_decode(file_get_contents("php://input"), true);
		DB::table('roomHistory_'.$boxToken)
			->where('room_history_id', $video["room_history_id"])
			->update(['video_status' => $video["video_status"]]);

		http_response_code(200);
		return json_encode("Video updated successfully");
	}

	// Shuffles playlist
	public function shuffle($boxToken){

		$query = DB::table('roomHistory_'.$boxToken)
			->whereIn('video_status', [0, 3]);

		$queue = $query
			->select('room_history_id')
			->inRandomOrder()
			->get();

		$order = $query
			->select('playlist_order')
			->inRandomOrder()
			->get();

		for($i = 0; $i < sizeof($queue); $i++){
			DB::table('roomHistory_'.$boxToken)
				->where('room_history_id', $queue[$i]->room_history_id)
				->update(['playlist_order' => $order[$i]->playlist_order]);
		}

		http_response_code(200);
		echo json_encode("Playlist shuffled succesfully");
	}

	// Swap two videos
	public function swap($boxToken){
		$action = json_decode(file_get_contents("php://input"), true);
		$table = 'roomHistory_'.$boxToken;

		if($action['direction'] == "up"){
			DB::table($table)
				->where('playlist_order', ++$action["playlist_order"])
				->decrement('playlist_order');

			DB::table($table)
				->where('room_history_id', $action["room_history_id"])
				->increment('playlist_order');
		}

		if($action['direction'] == "down"){
			DB::table($table)
				->where('playlist_order', --$action["playlist_order"])
				->increment('playlist_order');

			DB::table($table)
				->where('room_history_id', $action["room_history_id"])
				->decrement('playlist_order');
		}

		http_response_code(200);
		echo json_encode("Videos swapped successfully");
	}

	private function playing($boxToken, $playlistOrder){
		$now = new \DateTime();
		DB::table('roomHistory_'.$boxToken)
			->where('playlist_order', $playlistOrder)
			->update([
				'video_status' => 1,
				'history_start' => $now
			]);
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