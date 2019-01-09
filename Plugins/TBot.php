<?php

namespace Modules\TBot\Http\Controllers;

use App\Facades\DeveloppementTools;
use App\Http\Controllers\AppController;
use Carbon\Carbon;
use Illuminate\Routing\Controller;
use Modules\TBot\Entities\TBOTJSONACCOUNT;
use Illuminate\Http\Request;
use Modules\TBot\Http\Controllers\API\TrixCoreController;

class TBotController extends Controller
{
    // Documentation : https://docs.trixcms.eu/

    /**
     * TBotController constructor.
     */
    public function __construct()
    {
        DeveloppementTools::shareVar(['Module' => $this, 'Extra' => (new ExtraController()), 'TrixBot' => (new TrixCoreController())]);
    }

    /**
     * @return mixed
     * @throws \Modules\TBot\Exceptions\APIConnectFail
     */
    public function configuration()
    {
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $apiUrl = $protocol . $_SERVER['HTTP_HOST'] . '/dashboard/trixbot/json/send/' . sha1(env('APP_KCFT'));
        $TBot = (new TrixCoreController());

        if($TBot->check() && $TBot->getGuild()->verification_level == 0) $CheckVerif = $this->LangChoose('ANYCHANNEL'); elseif($TBot->check() && $TBot->getGuild()->verification_level == 1) $CheckVerif = $this->LangChoose('ONECHANNEL'); elseif($TBot->check() && $TBot->getGuild()->verification_level == 2) $CheckVerif = $this->LangChoose('TWOCHANNEL'); elseif($TBot->check() && $TBot->getGuild()->verification_level == 3) $CheckVerif = $this->LangChoose('THREECHANNEL'); elseif($TBot->check() && $TBot->getGuild()->verification_level == 4) $CheckVerif = $this->LangChoose('FOURCHANNEL'); else $CheckVerif = null;

        if((new TBOTJSONACCOUNT())->exists())
            $get = [
                "username" => (new TBOTJSONACCOUNT())->find(1)->toArray()['username'],
                "password" => hash('sha256', (new TBOTJSONACCOUNT())->find(1)->toArray()['password'])
            ];
        else
            $get = [
              "username" => "No data",
              "password" => "No data"
            ];

        DeveloppementTools::shareVar(['ApiURL' => $apiUrl, 'get' => $get, 'CheckVerif' => $CheckVerif]);
        DeveloppementTools::moduleSetViews('Configuration - TrixBot', 'configuration', 'TBot::admin.configuration', 'TBot');
        return DeveloppementTools::loadView(true);
    }

    /**
     * @param Request|null $request
     * @return mixed
     */
    public function admin_configuration(Request $request = null)
    {
        $username = htmlspecialchars($request->input('username'));
        $password = hash('sha256', $request->input('password'));

        if(!(new TBOTJSONACCOUNT())->exists()) {
            if(isset($username) && !empty($username) && isset($password) && !empty($password)) {
                (new TBOTJSONACCOUNT())->insert([
                   "username" => $username,
                   "password" => $password,
                   "created_at" => Carbon::now(),
                   "updated_at" => Carbon::now()
                ]);
                return DeveloppementTools::sendMessage($this->LangChoose('FILLCONFIGSUCCESS'), 'success');
            } else {
                return DeveloppementTools::sendMessage($this->LangChoose('ERRFILLCONFIG'), 'error');
            }
        } else {
            if(isset($password) && !empty($password)) {
                (new TBOTJSONACCOUNT())->find(1)->update([
                    "username" => $username,
                    "password" => $password
                ]);
                return DeveloppementTools::sendMessage($this->LangChoose('FILLCONFIGSUCCESSUPDATE'), 'success');
            } else {
                return DeveloppementTools::sendMessage($this->LangChoose('ERRFILLCONFIGUPDATE'), 'error');
            }
        }
    }

    /**
     * @param Request|null $request
     * @return mixed
     * @throws \Modules\TBot\Exceptions\APIConnectFail
     */
    public function admin_configuration_edit(Request $request = null)
    {
        $channelVerif = intval($request->input('channel_verif'));
        $channel_name = htmlspecialchars($request->input('channel_name'));
        $channel_select_region = $request->input('channel_select_region');

        if(isset($channelVerif) && !empty($channelVerif)) {
            (new TrixCoreController())->editGuild($channel_name, $channelVerif, $channel_select_region);
            return DeveloppementTools::sendMessage($this->LangChoose('SUCCESSEDITGUILD'), 'success');
        } else {
            return DeveloppementTools::sendMessage($this->LangChoose('ERRFILLCONFIGUPDATE'), 'error');
        }
    }

    /**
     * @return mixed
     * @throws \Modules\TBot\Exceptions\APIConnectFail
     */
    public function lists()
    {
        $Tbot = new TrixCoreController();
        $channels = $Tbot->getGuild('channels');
        $limit = (isset($_GET['limits'])) ? $_GET['limits'] : 30;
        $members = $Tbot->getListMembers(intval($limit));
        DeveloppementTools::shareVar(['channels' => $channels, 'members' => $members]);

        DeveloppementTools::moduleSetViews('Listes - TrixBot', 'lists', 'TBot::admin.list', 'TBot');
        return DeveloppementTools::loadView(true);
    }

    /**
     * @param $userId
     * @return mixed
     * @throws \Modules\TBot\Exceptions\APIConnectFail
     */
    public function ban_user_lists($userId)
    {
        $reason = htmlspecialchars(request()->input('reason'));
        (new TrixCoreController())->banUser(intval($userId), null, $reason);
        return DeveloppementTools::sendMessage($this->LangChoose('SUCCESSBANUSER'), 'success');
    }

    /**
     * @param $userId
     * @return mixed
     * @throws \Modules\TBot\Exceptions\APIConnectFail
     */
    public function kick_user_lists($userId)
    {
        (new TrixCoreController())->kickUser(intval($userId));
        return DeveloppementTools::sendMessage($this->LangChoose('SUCCESSKICKUSER'), 'success');
    }

    /**
     * @return mixed
     * @throws \Modules\TBot\Exceptions\APIConnectFail
     */
    public function actions()
    {
        $Tbot = new TrixCoreController();
        $channels = $Tbot->getGuild('channels');
        $roles = $Tbot->getGuild('roles');

        DeveloppementTools::shareVar(['channels' => $channels, 'roles' => $roles]);
        DeveloppementTools::moduleSetViews('Actions - TrixBot', 'actions', 'TBot::admin.actions', 'TBot');
        return DeveloppementTools::loadView(true);
    }

    /**
     * @param Request|null $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function actions_search_user(Request $request = null)
    {
        $userId = htmlspecialchars($request->input('user'));
        if(isset($userId) && !empty($userId)) {
            try {
                $get = (new TrixCoreController())->getUser(intval($userId));
                if($get->bot) {
                    return response()->json([
                        "icon" => "https://cdn.discordapp.com/avatars/{$get->id}/{$get->avatar}.png",
                        "pseudo" => $get->username . ' (Bot)',
                        "userId" => $userId
                    ]);
                }
                return response()->json([
                    "icon" => "https://cdn.discordapp.com/avatars/{$get->id}/{$get->avatar}.png",
                    "pseudo" => $get->username,
                    "userId" => $userId
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    "icon" => "http://2.bp.blogspot.com/-dZAjnKpJGFo/UIQX7nTg6sI/AAAAAAAAD9A/QJq2nuR9EE8/s1600/04.png",
                    "pseudo" => $this->LangChoose('USERDONTEXIST')
                ]);
            }
        } else {
            return response()->json([
                "icon" => "https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTm7HVynrtXQyVPs21knU2B8ydjYMzU5ef4j7CZFskL9733Tv2v",
                "pseudo" => $this->LangChoose('FILLPROBLEM'),
                "userId" => $userId
            ]);
        }
    }

    /**
     * @param Request|null $request
     * @return mixed
     * @throws \Modules\TBot\Exceptions\APIConnectFail
     */
    public function actions_send_announce(Request $request = null)
    {
        $channel = $request->input('channel');
        $message = htmlspecialchars($request->input('announce'));

        if(isset($message) && !empty($message)) {
            (new TrixCoreController())->sendAnnounce($message, intval($channel));
            $getNameChannel = (new TrixCoreController())->getChannelName(intval($channel));
            return DeveloppementTools::sendMessage($this->LangChoose('SENDSUCCESSANNOUNCE') . ' ' . $getNameChannel, 'success');
        } else {
            return DeveloppementTools::sendMessage($this->LangChoose('ERRFILLANNOUNCE'), 'error');
        }
    }

    /**
     * @param Request|null $request
     * @return mixed
     * @throws \Modules\TBot\Exceptions\APIConnectFail
     */
    public function actions_add_role(Request $request = null)
    {
        $name = htmlspecialchars($request->input('name'));
        $permission = htmlspecialchars($request->input('permission'));
        $color = htmlspecialchars($request->input('color'));

        $permission = ($permission == "") ? null : $permission;
        $color = ($color == "") ? null : intval($permission);

        if(isset($name) && !empty($name)) {
            (new TrixCoreController())->addRole($name, $permission, intval($color));
            return DeveloppementTools::sendMessage($this->LangChoose('SUCCESSADDROLE'), 'success');
        } else {
            return DeveloppementTools::sendMessage($this->LangChoose('ERRFILLADDROLE'), 'error');
        }
    }

    /**
     * @param Request|null $request
     * @return mixed
     * @throws \Modules\TBot\Exceptions\APIConnectFail
     */
    public function actions_convert_to_userid(Request $request = null)
    {
        $name = htmlspecialchars($request->input('name'));
        if(isset($name) && !empty($name)) {
            $id = (new TrixCoreController())->convertToUserId($name);
            if(!is_null($id)) {
                return DeveloppementTools::sendMessage($this->LangChoose('SUCCESSCONVERT') . $id, 'success');
            } else {
                return DeveloppementTools::sendMessage($this->LangChoose('USERDONTEXIST'), 'error');
            }
        } else {
            return DeveloppementTools::sendMessage($this->LangChoose('ERRFILLCONVERT'), 'error');
        }
    }

    /**
     * @param Request|null $request
     * @return mixed
     */
    public function actions_convert_to_username(Request $request = null)
    {
        $id = htmlspecialchars($request->input('name'));
        if(isset($id) && !empty($id)) {
            try {
                $username = (new TrixCoreController())->convertToUsername(intval($id));
                return DeveloppementTools::sendMessage($this->LangChoose('SUCCESSCONVERTTOUSERNAME') . $username, 'success');
            } catch(\Exception $e) {
                return DeveloppementTools::sendMessage($this->LangChoose('USERDONTEXIST'), 'error');
            }
        } else {
            return DeveloppementTools::sendMessage($this->LangChoose('ERRFILLCONVERT'), 'error');
        }
    }

    /**
     * @param Request|null $request
     * @return mixed
     */
    public function actions_kick_user(Request $request = null)
    {
        $id = htmlspecialchars($request->input('user'));
        if(isset($id) && !empty($id)) {
            try {
                (new TrixCoreController())->kickUser(intval($id));
                return DeveloppementTools::sendMessage($this->LangChoose('SUCCESSKICKUSER'), 'success');
            } catch(\Exception $e) {
                return DeveloppementTools::sendMessage($this->LangChoose('USERDONTEXIST'), 'error');
            }
        } else {
            return DeveloppementTools::sendMessage($this->LangChoose('ERRFILLCONVERT'), 'error');
        }
    }

    /**
     * @param Request|null $request
     * @return mixed
     */
    public function actions_ban_user(Request $request = null)
    {
        $id = htmlspecialchars($request->input('user'));
        $reason = htmlspecialchars($request->input('reason'));

        $reason = ($reason == "") ? null : $reason;

        if(isset($id) && !empty($id)) {
            try {
                (new TrixCoreController())->banUser(intval($id), null, $reason);
                return DeveloppementTools::sendMessage($this->LangChoose('SUCCESSBANUSER'), 'success');
            } catch(\Exception $e) {
                return DeveloppementTools::sendMessage($this->LangChoose('USERDONTEXIST'), 'error');
            }
        } else {
            return DeveloppementTools::sendMessage($this->LangChoose('ERRFILLCONVERT'), 'error');
        }
    }

    /**
     * @param Request|null $request
     * @return mixed
     */
    public function actions_add_role_to_member(Request $request = null)
    {
        $id = htmlspecialchars($request->input('user'));
        $role = $request->input('rank');

        if(isset($id) && !empty($id)) {
            try {
                (new TrixCoreController())->addRoleToMember($role, intval($id));
                return DeveloppementTools::sendMessage($this->LangChoose('SUCCESSADDROLETOUSER'), 'success');
            } catch(\Exception $e) {
                return DeveloppementTools::sendMessage($this->LangChoose('USERDONTEXIST'), 'error');
            }
        } else {
            return DeveloppementTools::sendMessage($this->LangChoose('ERRFILLCONVERT'), 'error');
        }
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function send_json()
    {
        if((new TBOTJSONACCOUNT())->exists()) $tbja = (new TBOTJSONACCOUNT())->find(1)->toArray(); else $tbja = ["username" => null, "password" => null];
        return response()->json([
          [
              'username' => $tbja['username'],
              'password' => $tbja['password'],
              'key' => base64_encode(env('APP_KCFT'))
          ]
        ]);
    }

    /**
     * @param $userId
     * @return mixed
     */
    public function users_config($userId)
    {
        DeveloppementTools::shareVar(['userId' => intval($userId)]);

        DeveloppementTools::moduleSetViews('Configuration User TrixBot', 'user_actions', 'TBot::admin.user', 'TBot');
        return DeveloppementTools::loadView(true);
    }

    /**
     * @param $message
     * @return mixed
     */
    public function LangChoose($message)
    {
        return DeveloppementTools::getLangModules('TBot', $message);
    }
}
