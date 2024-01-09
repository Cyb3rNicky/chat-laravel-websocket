<?php

namespace App\Livewire;

use App\Models\Chat;
use App\Models\Contact;
use App\Models\Message;
use App\Notifications\NewMessage;
use Illuminate\Support\Facades\Notification;
use Livewire\Component;

class ChatComponent extends Component
{

    public $search;
    public $contacts;
    public $contactChat, $chat;
    public $bodyMessage;
    public $messages;
    public $chats;


    public function getListeners()
    {

        $user_id = auth()->user()->id;

        return [
            "echo-notification:App.Models.User.{$user_id},notification" => 'render',
        ];
    }

    public function getContactsProperty()
    {
       $this->contacts = Contact::where('user_id', auth()->id())->when($this->search, function ($query) {
            $query->where(function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                    ->orWhereHas('user', function ($query) {
                        $query->where('email', 'like', '%' . $this->search . '%');
                    });
            });
        })->get() ?? [];
        
        return $this->contacts;
    }

    public function getMessageProperty()
    {
        $this->messages = $this->chat ? $this->chat->messages : [];

        return $this->messages;
    }

    public function getChatsProperty()
    {
        $this->chats = auth()->user()->chats()->get()->sortByDesc('last_message_at'); 

        return $this->chats;
    }

    public function getUsersNotificationsProperty()
    {
        return $this->chat ? $this->chat->users->where('id', '!=', auth()->id()) : []; 
    }

    public function open_chat_contact(Contact $contact)
    {
        $chat = auth()->user()->chats()
                    ->whereHas('users', function($query) use($contact){
                        $query->where('user_id', $contact->contact_id);
                    })
                    ->has('users', 2)
                    ->first();

        if($chat){
            $this->chat = $chat;
            $this->reset('contactChat', 'bodyMessage', 'search');
        }else{
            $this->contactChat = $contact;
            $this->reset('chat', 'bodyMessage', 'search');            
        }
    }

    public function open_chat(Chat $chat)
    {
        $this->chat = $chat;
        $this->reset('contactChat', 'bodyMessage');  
    }

    public function sendMessage()
    {
        $this->validate([
            'bodyMessage' => 'required'
        ], [
            'bodyMessage.required' => 'El campo mensaje es obligatorio'
        ]);

        if(!$this->chat){
            $this->chat = Chat::create();
            $this->chat->users()->attach([auth()->user()->id, $this->contactChat->contact_id]);
        }

        $this->chat->messages()->create([
            'content'    => $this->bodyMessage,
            'user_id' => auth()->user()->id
        ]);

        Notification::send($this->users_notifications, new NewMessage());

        $this->reset('bodyMessage', 'contactChat');
    }

    public function render()
    {
        $this->getContactsProperty();
        $this->getMessageProperty();
        $this->getChatsProperty();

        if ($this->chat) {
            $this->dispatch('scrollIntoView');
        }

        return view('livewire.chat-component')->layout('components.layouts.chat');
    }
}
