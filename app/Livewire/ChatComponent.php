<?php

namespace App\Livewire;

use App\Models\Contact;
use Livewire\Component;

class ChatComponent extends Component
{

    public $search;
    public $contacts;

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

    public function render()
    {
        $this->getContactsProperty();
        return view('livewire.chat-component')->layout('components.layouts.chat');
    }
}
