<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class PageController extends Controller
{
    public function about() { return view('pages.about'); }
    public function privacy() { return view('pages.privacy'); }
    public function terms() { return view('pages.terms'); }
    public function contact() { return view('pages.contact'); }

    public function submitContact(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150'],
            'subject' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $to = config('mail.from.address');
        if ($to) {
            Mail::raw(
                "Name: {$data['name']}\nEmail: {$data['email']}\n\n{$data['message']}",
                fn ($mail) => $mail->to($to)
                    ->replyTo($data['email'], $data['name'])
                    ->subject('[JobsPic Contact] '.$data['subject'])
            );
        }

        return back()->with('contact_success', 'Thank you! Your message has been sent. We will get back to you soon.');
    }
}
