<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use App\Enums\EmailTemplateType;
use Illuminate\Database\Seeder;

class EmailTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Email 1: Registration Confirmation (Immediately After Invoice & Pre-Training Form)
        EmailTemplate::updateOrCreate(
            ['type' => EmailTemplateType::REGISTRATION_CONFIRMATION->value],
            [
                'type' => EmailTemplateType::REGISTRATION_CONFIRMATION,
                'subject' => 'Confirm Your Registration for Datastat Institute Training',
                'body_html' => <<<'HTML'
<p>Dear {{participant_name}},</p>

<p>I hope this message finds you well.</p>

<p>We are reaching out to confirm that you received the invoice and pre-training form for your upcoming program with Datastat Institute, scheduled for <strong>{{training_date}}</strong> in {{training_location}}.</p>

<p>Completing the pre-training form and confirming your attendance ensures that we tailor the training experience to your professional needs, maximizing learning outcomes and practical impact.</p>

<p>Should you need any assistance with the payment process or have any special requirements, our team is ready to support you.</p>

<div class="highlight">
    <p><strong>Join thousands of professionals</strong> who have advanced their careers through our expert-led training programs. We look forward to welcoming you and helping you unlock your full potential.</p>
</div>

<p>Warm regards,<br>
<strong>{{coordinator_name}}</strong><br>
Training Coordinator<br>
Datastat Institute</p>
HTML,
                'is_active' => true,
            ]
        );

        // Email 2: Attendance Confirmation (Sent 2 Weeks Before Training)
        EmailTemplate::updateOrCreate(
            ['type' => EmailTemplateType::ATTENDANCE_CONFIRMATION->value],
            [
                'type' => EmailTemplateType::ATTENDANCE_CONFIRMATION,
                'subject' => 'Confirm Your Attendance – Datastat Institute Training',
                'body_html' => <<<'HTML'
<p>Dear {{participant_name}},</p>

<p>We are excited to host you at the Datastat Institute training program from <strong>{{training_date}}</strong> in {{training_location}}.</p>

<p>To finalize logistics and ensure a seamless learning experience, please confirm your attendance. Your confirmation allows us to prepare training materials, resources, and any personalized support you may require.</p>

<p><strong>Key Details:</strong></p>
<ul>
    <li><strong>Course:</strong> {{course_title}}</li>
    <li><strong>Start Date:</strong> {{start_date}}</li>
    <li><strong>End Date:</strong> {{end_date}}</li>
    <li><strong>Location:</strong> {{training_location}}</li>
</ul>

<p>If you have dietary preferences, accessibility needs, or specific topics you would like the trainer to cover, kindly let us know. Our aim is to provide a practical, engaging, and career-transforming experience tailored to your professional growth.</p>

<p>We look forward to welcoming you to Datastat Institute and supporting your journey to professional excellence.</p>

<p>Best regards,<br>
<strong>{{coordinator_name}}</strong><br>
Training Coordinator<br>
Datastat Institute</p>
HTML,
                'is_active' => true,
            ]
        );

        // Email 3: Final Preparations (Sent 1 Week Before Training)
        EmailTemplate::updateOrCreate(
            ['type' => EmailTemplateType::FINAL_PREPARATION->value],
            [
                'type' => EmailTemplateType::FINAL_PREPARATION,
                'subject' => 'Final Preparations – Datastat Institute Training',
                'body_html' => <<<'HTML'
<p>Dear {{participant_name}},</p>

<p>The Datastat Institute training program is just a few days away, taking place from <strong>{{training_date}}</strong> in {{training_location}}.</p>

<p>If you haven't already, please confirm your attendance and ensure your pre-training form and payment are completed. This allows us to prepare your training materials, participant resources, and on-site support.</p>

<div class="highlight">
    <p><strong>What to Expect:</strong></p>
    <ul>
        <li>Expert-led, practical learning experience</li>
        <li>Actionable skills and insights to advance your career</li>
        <li>Comprehensive training materials and resources</li>
        <li>Interactive sessions and real-world case studies</li>
    </ul>
</div>

<p><strong>Training Details:</strong></p>
<ul>
    <li><strong>Course:</strong> {{course_title}}</li>
    <li><strong>Dates:</strong> {{training_date}}</li>
    <li><strong>Location:</strong> {{training_location}}</li>
</ul>

<p>We are thrilled to have you join us for an expert-led, practical learning experience designed to equip you with actionable skills and insights to advance your career.</p>

<p>For any questions or assistance, our team is available and ready to support you.</p>

<p>Warm regards,<br>
<strong>{{coordinator_name}}</strong><br>
Training Coordinator<br>
Datastat Institute</p>
HTML,
                'is_active' => true,
            ]
        );
    }
}
