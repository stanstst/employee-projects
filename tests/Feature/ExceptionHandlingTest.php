<?php

namespace Tests\Feature;

use App\Services\CommonWorkCalculator;
use App\Services\OverlapDays;
use Illuminate\Http\UploadedFile;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;
use Throwable;

class ExceptionHandlingTest extends TestCase
{
    private const URI = '/api/calculate-common-projects';

    public function test_invalid_argument_exception_returns_400_with_its_message(): void
    {
        $this->calculatorThrows(new InvalidArgumentException('Bad data'));

        $this->upload()
            ->assertStatus(400)
            ->assertExactJson(['message' => 'Bad data']);
    }

    public function test_other_exceptions_return_a_generic_500_without_details(): void
    {
        $this->calculatorThrows(new RuntimeException('secret detail'));

        $this->upload()
            ->assertStatus(500)
            ->assertExactJson(['message' => 'Server Error'])
            ->assertDontSee('secret detail');
    }

    public function test_bad_dates_return_400_with_a_clear_message(): void
    {
        $csv = "EmpID,ProjectID,DateFrom,DateTo\n143,12,2013-02-30,NULL\n";

        $this->post(self::URI, [
            'file' => UploadedFile::fake()->createWithContent('projects.csv', $csv),
        ])
            ->assertStatus(400)
            ->assertExactJson(['message' => 'Employee 143 has an invalid DateFrom on project 12: "2013-02-30". Use a valid date such as 2013-11-01.']);
    }

    public function test_euro_date_format_gives_the_same_result_as_the_default_format(): void
    {
        $default = "EmpID,ProjectID,DateFrom,DateTo\n1,10,2013-11-01,2014-01-05\n2,10,2013-12-01,2014-03-10\n";
        $euro = "EmpID,ProjectID,DateFrom,DateTo\n1,10,01.11.2013,05.01.2014\n2,10,01.12.2013,10.03.2014\n";

        $expected = $this->post(self::URI, [
            'file' => UploadedFile::fake()->createWithContent('default.csv', $default),
        ])->assertOk()->json();

        $this->post(self::URI, [
            'file' => UploadedFile::fake()->createWithContent('euro.csv', $euro),
            'dateFormat' => 'euro',
        ])
            ->assertOk()
            ->assertExactJson($expected);
    }

    public function test_dates_in_the_wrong_format_return_400_with_a_hint(): void
    {
        $csv = "EmpID,ProjectID,DateFrom,DateTo\n143,12,2013-11-01,NULL\n";

        $this->post(self::URI, [
            'file' => UploadedFile::fake()->createWithContent('projects.csv', $csv),
            'dateFormat' => 'euro',
        ])
            ->assertStatus(400)
            ->assertExactJson(['message' => 'Employee 143 has an invalid DateFrom on project 12: "2013-11-01". Use a valid date such as 01.11.2013.']);
    }

    public function test_validation_errors_keep_their_422_response(): void
    {
        $this->postJson(self::URI)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('file');
    }

    public function test_unknown_routes_keep_their_404_response(): void
    {
        $this->getJson('/api/missing')
            ->assertNotFound()
            ->assertExactJson(['message' => 'The route api/missing could not be found.']);
    }

    public function test_wrong_method_keeps_its_405_response_without_details(): void
    {
        $this->getJson(self::URI)
            ->assertMethodNotAllowed()
            ->assertHeader('Allow', 'POST')
            ->assertJsonMissingPath('exception')
            ->assertJsonMissingPath('trace');
    }

    private function upload()
    {
        $csv = "EmpID,ProjectID,DateFrom,DateTo\n1,10,2020-01-01,2020-02-01\n";

        return $this->post(self::URI, [
            'file' => UploadedFile::fake()->createWithContent('projects.csv', $csv),
        ]);
    }

    private function calculatorThrows(Throwable $exception): void
    {
        $this->app->instance(CommonWorkCalculator::class, new readonly class($exception) extends CommonWorkCalculator
        {
            public function __construct(private Throwable $exception)
            {
                parent::__construct(new OverlapDays);
            }

            public function calculate(array $input): array
            {
                throw $this->exception;
            }
        });
    }
}
