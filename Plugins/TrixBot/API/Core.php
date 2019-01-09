<?php

class TrixCoreController extends Controller
{
    // Documentation : https://docs.trixcms.eu/

    /**
     * @return mixed
     * @throws \Modules\TBot\Exceptions\APIConnectFail
     */
    public function guildId() {
        return intval((new APIConnect())->getItem('GuildId'));
    }

    /**
     * @return bool
     * @throws \Modules\TBot\Exceptions\APIConnectFail
     */
    public function check()
    {
        if(!$this->guildId()) {
            return false;
        }
        return true;
    }

    /**
     * @param $client
     * @return DiscordClient
     */
    public function RestConfig($client)
    {
        return new DiscordClient(['token' => (string) $client]);
    }

    /**
     * @param $guildId
     * @param int $limit
     * @param null $after
     * @return \RestCord\Model\Guild\GuildMember[]
     * @throws \Modules\TBot\Exceptions\APIConnectFail
     */
    public function getListMembers($limit = 30, $after = null)
    {
        return ($this->RestConfig($this->token))->guild->listGuildMembers(['guild.id' => $this->guildId(), 'limit' => $limit, 'after' => $after]);
    }

    /**
     * @param string $name
     * @param null $userId
     * @return JsonResponse|\RestCord\Model\Channel\Channel[]|\RestCord\Model\Guild\Ban[]|\RestCord\Model\Guild\Guild|\RestCord\Model\Guild\GuildEmbed|\RestCord\Model\Guild\GuildMember|\RestCord\Model\Permissions\Role[]
     * @throws \Modules\TBot\Exceptions\APIConnectFail
     */
    public function getGuild($name = "guild", $userId = null)
    {
        switch ($name) {
            case "guild":
                return ($this->RestConfig($this->token)->guild->getGuild(['guild.id' => $this->guildId()]));
                break;
            case "ban":
                return ($this->RestConfig($this->token)->guild->getGuildBans(['guild.id' => $this->guildId()]));
                break;
            case "channels":
                return ($this->RestConfig($this->token)->guild->getGuildChannels(['guild.id' => $this->guildId()]));
                break;
            case "Embed":
                return ($this->RestConfig($this->token)->guild->getGuildEmbed(['guild.id' => $this->guildId()]));
                break;
            case "members":
                return ($this->RestConfig($this->token)->guild->getGuildMember(['guild.id' => $this->guildId(), 'user.id' => $userId]));
                break;
            case "roles":
                return ($this->RestConfig($this->token)->guild->getGuildRoles(['guild.id' => $this->guildId()]));
                break;
            default:
                return JsonResponse::create(['type' => 404, 'message' => 'Not found parameter', 'now' => getLoadPageTime()]);
                break;
        }
    }

    /**
     * @param $userId
     * @return \RestCord\Model\User\User
     */
    public function getUser($userId)
    {
        return ($this->RestConfig($this->token))->user->getUser(['user.id' => $userId]);
    }

    /**
     * @return \RestCord\Interfaces\Channel
     */
    public function channels()
    {
        return ($this->RestConfig($this->token))->channel;
    }

    /**
     * @param $userId
     * @return int
     * @throws \Modules\TBot\Exceptions\APIConnectFail
     */
    public function convertToUsername($userId)
    {
        $user = $this->getGuild('members', $userId);
        if(!$user->user->bot) return $user->user->id;
    }

    /**
     * @param $username
     * @return JsonResponse|int
     * @throws \Modules\TBot\Exceptions\APIConnectFail
     */
    public function convertToUserId($username)
    {
        foreach($this->getListMembers() as $listMember) {
            if(in_array($listMember->user->username, [$username])) {
                return $listMember->user->id;
            }
            //return JsonResponse::create(['type' => 404, 'message' => 'Not found user in discord', 'now' => getLoadPageTime()]);
        }
    }

    /**
     * @param $announce
     * @param $channel
     * @param null $file
     * @return array
     * @throws \Modules\TBot\Exceptions\APIConnectFail
     */
    public function sendAnnounce($announce, $channel, $file = null)
    {
        $channel = (is_string($channel)) ? $this->getChannelName($channel) : $channel;
        return $this->channels()->createMessage(['channel.id' => $channel,
            'content' => $announce, 'file' => $file]);
    }

    /**
     * @param $channelId
     * @return \RestCord\Model\Channel\Channel
     */
    public function getChannelName($channelId)
    {
        return $this->channels()->getChannel(['channel.id' => $channelId])->name;
    }

    public function getRoleIdByName($rolename) {
        foreach($this->getGuild('roles') as $item) {
            if(in_array($item->name, [$rolename])) {
                return intval($item->id);
            }
        }
    }

    /**
     * @param $userId
     * @param null $dmd
     * @param null $reason
     * @return array
     * @throws \Modules\TBot\Exceptions\APIConnectFail
     */
    public function banUser($userId, $dmd = null, $reason = null)
    {
        return ($this->RestConfig($this->token)->guild->createGuildBan((['guild.id' => $this->guildId(), 'user.id' => $userId, 'delete-message-days' => $dmd, 'reason' => $reason])));
    }

    /**
     * @param $userId
     * @return array
     * @throws \Modules\TBot\Exceptions\APIConnectFail
     */
    public function kickUser($userId)
    {
        return ($this->RestConfig($this->token)->guild->removeGuildMember((['guild.id' => $this->guildId(), 'user.id' => $userId])));
    }

    /**
     * @param $rolename
     * @param $userId
     * @return array
     * @throws \Modules\TBot\Exceptions\APIConnectFail
     */
    public function addRoleToMember($rolename, $userId)
    {
        return ($this->RestConfig($this->token)->guild->addGuildMemberRole((['guild.id' => $this->guildId(), 'user.id' => $userId,
            'role.id' => $rolename])));
    }

    /**
     * @param $rolename
     * @param int $permission
     * @param int $color
     * @param null $hoist
     * @param null $mentionable
     * @return \RestCord\Model\Permissions\Role
     * @throws \Modules\TBot\Exceptions\APIConnectFail
     */
    public function addRole($rolename, $permission = 0, $color = 8421504, $hoist = null, $mentionable = null)
    {
        return ($this->RestConfig($this->token)->guild->createGuildRole((['guild.id' => $this->guildId(),
            'name' => $rolename, 'color' => $color, 'permission' => $permission, 'hoist' => $hoist, 'mentionable' => $mentionable])));
    }

    /**
     * @param null $name
     * @param null $verificationLevel
     * @param null $region
     * @param null $afk_timeout
     * @return \RestCord\Model\Guild\Guild
     * @throws \Modules\TBot\Exceptions\APIConnectFail
     */
    public function editGuild($name = null, $verificationLevel = null, $region = null, $afk_timeout = null)
    {
        return ($this->RestConfig($this->token))->guild->modifyGuild([
            "guild.id" => $this->guildId(),
            "name" => $name,
            "verification_level" => $verificationLevel,
            "region" => $region,
            "afk_timeout" => $afk_timeout
        ]);
    }
}
