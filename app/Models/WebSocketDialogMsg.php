<?php

namespace App\Models;

/**
 * WebSocket Dialog Message model
 *
 * @property int $id
 * @property int $dialog_id
 * @property int $user_id
 * @property string $type
 * @property string $content
 * @property string $file_url
 * @property string $file_name
 * @property int $file_size
 * @property int $reply_id
 * @property int $is_recalled
 * @property string $created_at
 */
class WebSocketDialogMsg extends AbstractModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'web_socket_dialog_messages';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'dialog_id',
        'user_id',
        'type',
        'content',
        'file_url',
        'file_name',
        'file_size',
        'reply_id',
        'is_recalled',
        'created_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime',
    ];

    /**
     * Message type constants
     */
    const TYPE_TEXT = 'text';
    const TYPE_IMAGE = 'image';
    const TYPE_FILE = 'file';
    const TYPE_AUDIO = 'audio';
    const TYPE_VIDEO = 'video';
    const TYPE_SYSTEM = 'system';

    /**
     * Get the dialog
     */
    public function dialog()
    {
        return $this->belongsTo(WebSocketDialog::class, 'dialog_id');
    }

    /**
     * Get the sender
     */
    public function sender()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get reply message
     */
    public function reply()
    {
        return $this->belongsTo(WebSocketDialogMsg::class, 'reply_id');
    }

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->created_at) {
                $model->created_at = now();
            }
        });
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
            'dialog_id' => $this->dialog_id,
            'user_id' => $this->user_id,
            'sender' => $this->sender ? $this->sender->toSimple() : null,
            'type' => $this->type,
            'content' => $this->content,
            'file_url' => $this->file_url,
            'file_name' => $this->file_name,
            'file_size' => $this->file_size,
            'reply_id' => $this->reply_id,
            'reply' => $this->reply ? $this->reply->toInfo() : null,
            'is_recalled' => $this->is_recalled,
            'created_at' => $this->created_at ? $this->created_at->toDateTimeString() : null,
            'created_timestamp' => $this->created_at ? $this->created_at->timestamp : 0,
        ];
    }
}
