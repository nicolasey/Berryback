<?php
/**
 * Created by PhpStorm.
 * User: AngelZatch
 * Date: 24/02/2017
 * Time: 23:08
 */

namespace  App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Box;

class BoxController extends BaseController
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

	// GET all boxes
	public function index(){
		$stmt = "SELECT
		          box_token AS token,
		          room_name AS title,
		          creation_date,
		          room_type AS type,
		          room_lang AS lang,
		          room_description AS description,
		          user_pseudo AS admin,
		          user_pp AS admin_picture,
		          u.user_token AS admin_token
		          FROM rooms r
		          LEFT JOIN user u ON r.room_creator = u.user_token";
		$results = DB::select($stmt);
		return json_encode($results);
	}

    // PUT the listing
    public function update()
    {

    }

    // POST a single box
    public function store()
    {

    }

    // GET a single box
    public function show($box)
    {
        $stmt = "SELECT
		          box_token AS token,
		          room_name AS title,
		          creation_date,
		          room_type AS type,
		          room_lang AS lang,
		          room_description AS description ,
		          user_pseudo AS admin,
		          user_pp AS admin_picture,
		          u.user_token AS admin_token
		          FROM rooms r
		          LEFT JOIN user u ON r.room_creator = u.user_token
		          WHERE box_token = :box";
        $box_details = DB::select($stmt, ['box' => $box]);

        return json_encode($box_details[0]);
    }

    // PUT a single box
    public function edit()
    {

    }

    // DELETE a single box
    public function destroy($box)
    {
        // First, we drop all the tables used for the box to work
        DB::statement("DROP TABLE roomChat_$box");
        DB::statement("DROP TABLE roomHistory_$box");
        DB::statement("DROP TABLE roomUsers_$box");

        // We then delete the entry from the rooms table
        DB::delete("DELETE FROM rooms WHERE box_token = :box", ['box' => $box]);

    }

    // GET list of users
    public function users($box)
    {
        $users = DB::select("SELECT
                            room_user_token,
                            room_user_state,
                            user_pseudo,
                            user_pp
                            FROM roomUsers_$box ru
                            LEFT JOIN user u ON ru.room_user_token = u.user_token");

        return json_encode($users);
    }

    // POST to playlist
    public function submit($box)
    {
        echo "bonjour";
        //$link = json_decode(file_get_contents("php://input"), true);

        // Check song base
        //$base = DB::select("SELECT * FROM song_base WHERE link = '$link'");

        // If the video doesn't exist in the Berrybox base
        /*if($base->rowCount() == 0){
            try {
                // We try to get the contents from the YouTube databases
                $content = file_get_contents("http://youtube.com/get_video_info?video_id=".$link);
                parse_str($content, $ytarr):
                $title = addslashes($ytarr["title"]);
                $pending = 0;
                if($title == ""){
                    $title = "-";
                    $pending = 1;
                }
            } catch(Exception $e){
                $title = "-";
                $pending = 1;
            }

            DB::post("INSERT INTO song_base(link, video_name, pending)
                      VALUES(:link, :title, :pending)",
                [
                    ':link' => $link,
                    ':title' => $title,
                    ':pending' => $pending
                ]
            );
        } else {
            echo $base->title;
        }*/

    }

}
