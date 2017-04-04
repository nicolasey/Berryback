<?php
/**
 * Created by PhpStorm.
 * User: AngelZatch
 * Date: 02/04/2017
 * Time: 11:20
 */

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Box;

class ChatController extends BaseController
{
	// Fetches all messages from a box
	public function listing($box){
		$stmt = "SELECT message_id AS id,
					message_scope AS scope,
					message_type AS type,
					u.user_pseudo AS author,
					message_author AS author_token,
					up.up_color AS author_color,
					message_destination AS destination,
					message_time AS time,
					message_contents AS contents
 					FROM roomChat_$box rc
 					JOIN user u ON rc.message_author = u.user_token
 					JOIN user_preferences up ON rc.message_author = up.user_token
 					ORDER BY message_id ASC";
		$chat = DB::select($stmt);

		return json_encode($chat);
	}

	// POST a message to a chat
	public function store($box){
		$data = json_decode(file_get_contents("php://input"), true);

		$now = new \DateTime();
		$insert = DB::select("INSERT INTO roomchat_$box(
		message_scope, message_type, message_author, message_destination, message_time, message_contents)
		VALUES (:scope, :type, :author, :destination, :time, :contents)",
			[
				':scope' => $data['scope'],
				':type' => $data['type'],
				':author' => $data['author'],
				':destination' => $data['destination'],
				':time' => $now,
				':contents' => $data['contents']
			]
		);
		echo json_encode("Message posted successfully");
		http_response_code(201);

	}

	// Edit a message
	public function update($box){
		$data = json_decode(file_get_contents("php://input"), true);

		$now = new \DateTime();
		$stmt = "UPDATE roomchat_$box
				SET message_contents = :contents
				 WHERE message_id = :id";
		$update = DB::select($stmt,
			[
				':id' => $data['id'],
				':contents' => $data['contents']
			]
		);
		echo json_encode("Message edited successfully");
		http_response_code(200);
	}

	// Delete a message
	public function destroy($box, $id){
		$stmt = "DELETE FROM roomchat_$box
				WHERE message_id = :id";

		$delete = DB::select($stmt, [':id' => $id]);

		http_response_code(204);
	}
}