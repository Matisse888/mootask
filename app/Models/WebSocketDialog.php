<?php

namespace App\Models;

/**
 * WebSocket Dialog model (Instant Messaging)
 *
 * @property int $id
 * @property string $type
 * @property string $name
 * @property string $avatar
 * @property int $user_id
 * @property int $last_msg_id
 * @property int $unread_count
 * @property string $last_msg_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class WebSocketDialog extends AbstractModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'web_socket_dialogs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'type',
        'name',
        'avatar',
        'user_id',
        'last_msg_id',
        'unread_count',
        'last_msg_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'last_msg_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Dialog type constants
     */
    const TYPE_PRIVATE = 'private';
    const TYPE_GROUP = 'group';

    /**
     * Get the users
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'web_socket_dialog_users', 'dialog_id', 'user_id')
            ->withPivot(['role', 'last_read_at', 'created_at']);
    }

    /**
     * Get the creator
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get messages
     */
    public function messages()
    {
        return $this->hasMany(WebSocketDialogMsg::class, 'dialog_id')->orderBy('created_at', 'desc');
    }

    /**
     * Get last message
     */
    public function lastMessage()
    {
        return $this->belongsTo(WebSocketDialogMsg::class, 'last_msg_id');
    }

    /**
     * Convert to info array
     *
     * @return array
     */
    public function toInfo(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'name' => $this->name,
            'avatar' => $this->avatar,
            'user_id' => $this->user_id,
            'creator' => $this->creator ? $this->creator->toSimple() : null,
            'last_msg_id' => $this->last_msg_id,
            'last_message' => $this->lastMessage ? $this->lastMessage->toInfo() : null,
            'unread_count' => $this->unread_count ?? 0,
            'last_msg_at' => $this->last_msg_at ? $this->last_msg_at->toDateTimeString() : null,
            'members' => $this->users ? $this->users->map(function ($user) {
                return $user->toSimple();
            }) : [],
            'created_at' => $this->created_at ? $this->created_at->toDateTimeString() : null,
        ];
    }
}
