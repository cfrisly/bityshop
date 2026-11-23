<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function al_registrarse_se_envia_el_correo_y_la_cuenta_queda_sin_verificar()
    {
        Notification::fake();

        $this->post(route('register'), [
            'name' => 'Ana Prueba',
            'email' => 'ana@example.com',
            'password' => 'secreta123',
            'password_confirmation' => 'secreta123',
        ])->assertRedirect('/home');

        $user = User::where('email', 'ana@example.com')->firstOrFail();
        $this->assertFalse($user->hasVerifiedEmail());
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    #[Test]
    public function sin_verificar_no_puede_usar_mi_cuenta_ni_comprar_con_su_cuenta()
    {
        $user = User::create(['name' => 'Ana', 'email' => 'ana@example.com', 'password' => bcrypt('secreta123')]);

        $this->actingAs($user)->get('/home')->assertRedirect(route('verification.notice'));
        $this->actingAs($user)->get(route('checkout.show'))->assertRedirect(route('verification.notice'));
        $this->actingAs($user)->get(route('verification.notice'))->assertOk()->assertSee('ana@example.com');
    }

    #[Test]
    public function el_enlace_del_correo_verifica_la_cuenta()
    {
        $user = User::create(['name' => 'Ana', 'email' => 'ana@example.com', 'password' => bcrypt('secreta123')]);
        $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), ['id' => $user->id, 'hash' => sha1($user->email)]);

        $this->actingAs($user)->get($url)->assertRedirect('/home');

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $this->actingAs($user->fresh())->get('/home')->assertOk();
    }

    #[Test]
    public function un_enlace_alterado_no_verifica_la_cuenta()
    {
        $user = User::create(['name' => 'Ana', 'email' => 'ana@example.com', 'password' => bcrypt('secreta123')]);
        $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), ['id' => $user->id, 'hash' => sha1($user->email)]);

        $this->actingAs($user)->get($url . 'x')->assertForbidden();
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    #[Test]
    public function los_invitados_siguen_pudiendo_entrar_al_checkout()
    {
        $this->get(route('checkout.show'))->assertOk();
    }
}
