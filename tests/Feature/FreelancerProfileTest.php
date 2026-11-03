<?php

namespace Tests\Feature;

use App\Models\FreelancerProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

final class FreelancerProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_settings_page_shows_both_locales_and_does_not_create_an_empty_profile(): void
    {
        $this->withSession(['locale' => 'fa'])
            ->get(route('settings.freelancer.edit'))
            ->assertOk()
            ->assertSee('مشخصات پایه')
            ->assertSee('فناوری‌ها')
            ->assertSee('خدمات و تخصص‌ها');

        $this->withSession(['locale' => 'en'])
            ->get(route('settings.freelancer.edit'))
            ->assertOk()
            ->assertSee('Basic details')
            ->assertSee('Technologies')
            ->assertSee('Services and specialties');

        $this->assertDatabaseCount('freelancer_profiles', 0);
    }

    public function test_it_saves_profile_details_and_parses_comma_and_line_separated_lists(): void
    {
        $response = $this->withSession(['locale' => 'en'])->put(route('settings.freelancer.update'), [
            'display_name' => 'Test Freelancer',
            'professional_title' => 'Software Developer',
            'bio' => 'A sample introduction for automated tests.',
            'experience_years' => 5,
            'location' => 'Remote',
            'languages' => "English\nPersian",
            'services' => "Web development; Performance optimization",
            'technologies' => "PHP\nLaravel, Filament",
            'portfolio_url' => 'https://example.test/portfolio',
            'website_url' => 'https://example.test',
        ]);

        $response->assertRedirect(route('settings.freelancer.edit'))
            ->assertSessionHas('success');

        $this->assertDatabaseCount('freelancer_profiles', 1);

        $profile = FreelancerProfile::query()->sole();
        $this->assertSame('Test Freelancer', $profile->display_name_en);
        $this->assertSame('Software Developer', $profile->professional_title_en);
        $this->assertSame(5, $profile->experience_years);
        $this->assertSame(['English', 'Persian'], $profile->languages_en);
        $this->assertSame(['Web development', 'Performance optimization'], $profile->services_en);
        $this->assertSame(['PHP', 'Laravel', 'Filament'], $profile->technologies_en);
        $this->assertSame('https://example.test/portfolio', $profile->portfolio_url);
        $this->assertSame('https://example.test', $profile->website_url);
    }

    public function test_profile_form_shows_the_selected_language_and_keeps_translations_separate(): void
    {
        FreelancerProfile::query()->create([
            'profile_key' => 'primary',
            'display_name_fa' => 'نام فارسی آزمایشی',
            'display_name_en' => 'English Test Name',
            'professional_title_fa' => 'توسعه‌دهندهٔ نرم‌افزار',
            'professional_title_en' => 'Software Developer',
            'technologies_fa' => ['لاراول'],
            'technologies_en' => ['Laravel'],
        ]);

        $this->withSession(['locale' => 'fa'])
            ->get(route('settings.freelancer.edit'))
            ->assertOk()
            ->assertSee('value="نام فارسی آزمایشی"', false)
            ->assertSee('لاراول')
            ->assertDontSee('English Test Name');

        $this->withSession(['locale' => 'en'])
            ->get(route('settings.freelancer.edit'))
            ->assertOk()
            ->assertSee('value="English Test Name"', false)
            ->assertSee('Laravel')
            ->assertDontSee('نام فارسی آزمایشی');

        $this->withSession(['locale' => 'fa'])
            ->put(route('settings.freelancer.update'), [
                'display_name' => 'نام فارسی به‌روزشده',
                'technologies' => "لاراول\nفیلامنت",
            ])
            ->assertRedirect(route('settings.freelancer.edit'));

        $profile = FreelancerProfile::query()->sole();
        $this->assertSame('نام فارسی به‌روزشده', $profile->display_name_fa);
        $this->assertSame('English Test Name', $profile->display_name_en);
        $this->assertSame(['لاراول', 'فیلامنت'], $profile->technologies_fa);
        $this->assertSame(['Laravel'], $profile->technologies_en);
    }

    public function test_it_rejects_invalid_experience_and_non_http_urls_without_saving(): void
    {
        $this->from(route('settings.freelancer.edit'))
            ->put(route('settings.freelancer.update'), [
                'display_name' => 'Test Freelancer',
                'experience_years' => 61,
                'portfolio_url' => 'javascript:alert(1)',
                'website_url' => 'not-a-url',
            ])
            ->assertRedirect(route('settings.freelancer.edit'))
            ->assertSessionHasErrors(['experience_years', 'portfolio_url', 'website_url']);

        $this->assertDatabaseCount('freelancer_profiles', 0);
    }

    public function test_saving_again_updates_the_single_primary_profile(): void
    {
        $this->withSession(['locale' => 'en'])
            ->put(route('settings.freelancer.update'), ['display_name' => 'First Name']);
        $this->withSession(['locale' => 'en'])
            ->put(route('settings.freelancer.update'), ['display_name' => 'Updated Name']);

        $this->assertDatabaseCount('freelancer_profiles', 1);
        $this->assertSame('Updated Name', FreelancerProfile::query()->sole()->display_name_en);
    }

    public function test_saved_profile_is_prefilled_in_the_settings_form(): void
    {
        FreelancerProfile::query()->create([
            'profile_key' => 'primary',
            'display_name_en' => 'Test Freelancer',
            'technologies_en' => ['PHP', 'Laravel'],
        ]);

        $this->get(route('settings.freelancer.edit'))
            ->assertOk()
            ->assertSee('value="Test Freelancer"', false)
            ->assertSee('PHP')
            ->assertSee('Laravel');
    }

    public function test_memory_context_includes_saved_profile_details_and_technologies(): void
    {
        FreelancerProfile::query()->create([
            'profile_key' => 'primary',
            'display_name_fa' => 'فریلنسر آزمایشی',
            'display_name_en' => 'Test Freelancer',
            'technologies_fa' => ['پی‌اچ‌پی', 'لاراول'],
            'technologies_en' => ['PHP', 'Laravel'],
        ]);

        Artisan::call('memory:context', ['--locale' => 'fa']);
        $persianOutput = Artisan::output();

        $this->assertStringContainsString('"content_locale":"fa"', $persianOutput);
        $this->assertStringContainsString('"display_name":"فریلنسر آزمایشی"', $persianOutput);
        $this->assertStringContainsString('"technologies":["پی‌اچ‌پی","لاراول"]', $persianOutput);
        $persianProfile = substr(
            $persianOutput,
            strpos($persianOutput, 'FREELANCER PROFILE'),
            strpos($persianOutput, 'SYSTEM GUIDANCE') - strpos($persianOutput, 'FREELANCER PROFILE'),
        );
        $this->assertStringNotContainsString('Test Freelancer', $persianProfile);

        Artisan::call('memory:context', ['--locale' => 'en']);
        $englishOutput = Artisan::output();
        $this->assertStringContainsString('"content_locale":"en"', $englishOutput);
        $this->assertStringContainsString('"display_name":"Test Freelancer"', $englishOutput);
        $this->assertStringContainsString('"technologies":["PHP","Laravel"]', $englishOutput);
    }

    public function test_memory_context_rejects_unsupported_output_languages(): void
    {
        $this->assertSame(2, Artisan::call('memory:context', ['--locale' => 'de']));
    }

    public function test_public_database_seeder_does_not_create_a_freelancer_profile(): void
    {
        $this->seed();

        $this->assertDatabaseCount('freelancer_profiles', 0);
    }
}
