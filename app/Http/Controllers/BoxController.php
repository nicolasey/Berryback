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
use Illuminate\Support\Facades\Schema;
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
    public function index()
    {
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
        $data = json_decode(file_get_contents("php://input"), true);
        $data['creator'] = 'D1JU70';

        $boxToken = $this->generateBoxToken(15);

        $now = new \DateTime();

        $box = DB::table('rooms')->insert([
            'box_token' => $boxToken,
            'room_name' => $data['name'],
            'room_creator' => $data['creator'],
            'creation_date' => $now,
            'room_type' => 1,
            'room_lang' => 'en',
            'room_description' => $data['description'],
            'room_play_type' => 1,
            'room_protection' => 1,
            'room_active' => 1,
            'last_active_date' => $now
        ]);

        Schema::create('room_users_'.$boxToken, function($table)
        {
            $table->increments('room_user_entry');
            $table->string('room_user_token', 10);
            $table->tinyInteger('room_user_state')->default(1)->comment('1 : standard / 2 : creator / 3 : moderator / 4 : toed / 5 : banned');
            $table->tinyInteger('room_user_timeouts')->default(0)->comment('number of timeouts of this user');
            $table->dateTime('presence_stamp')->nullable();
            $table->dateTime('room_user_next_state_reset')->nullable();
        });

        Schema::create('room_chat_'.$boxToken, function($table)
        {
            $table->increments('message_id');
            $table->tinyInteger('message_scope')->comment('1 : all / 2 : creator / 3 : moderators / 4 : system / 5 : solo / 6: whisper');
            $table->tinyInteger('message_type')->comment('(subtype of scope 4); 1 : normal / 2 : play / 3 : skip / 4 : close / 5 : ignore / 6 : reinstate / 7 : reopen');
            $table->string('message_author', 10);
            $table->string('message_destination', 10)->nullable();
            $table->dateTime('message_time');
            $table->text('message_contents')->nullable();

        });

        Schema::create('room_history_'.$boxToken, function($table)
        {
            $table->increments('room_history_id');
            $table->integer('playlist_order')->comment('order of play');
            $table->integer('video_index')->comment('id in song_base table');
            $table->dateTime('history_time')->comment('subsmission timestamp');
            $table->dateTime('history_start')->nullable()->comment('play beginning timestamp');
            $table->string('history_user', 10);
            $table->tinyInteger('video_status')->default(0)->comment('0: queued / 1 : playing / 2 : played / 3 : ignored');
        });

        echo json_encode(array("token" => $boxToken));
        http_response_code(201);
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
        DB::statement("DROP TABLE room_chat_$box");
        DB::statement("DROP TABLE room_history_$box");
        DB::statement("DROP TABLE room_users_$box");

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
                            FROM room_users_$box ru
                            LEFT JOIN user u ON ru.room_user_token = u.user_token");

        return json_encode($users);
    }

    private function generateBoxToken($length)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
        $chars_length = strlen($characters);

        $token = '';
        for ($i = 0; $i < $length; $i++) {
            $token .= $characters[rand(0, $chars_length -1)];
        }

        $this->checkDuplicate($token, $length);

        return $token;
    }
    
    private function checkDuplicate($token, $length)
    {
        $result = DB::table('rooms')->where([
            ['box_token', $token]
        ])->get();

        if(sizeof($result) != 0){
            $this->generateBoxToken($length);
        }

        return $token;
    }
}
