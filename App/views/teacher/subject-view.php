<?php loadPartial('student-head', ['extraCss' => '/css/teacher-subject.css']) ?>

<div class="transition transition-1 is-active"></div>

<div class="subject-pick-container">
    <h2>Teacher Subject</h2>
    <p>Select the subject and class you are setting before login.</p>

    <form action="/teacher/login" method="GET">
        <label for="subject">Subject</label>
        <select name="subject" id="subject" class="select" required>
            <option value="english" selected>English</option>
            <option value="mathematics">Mathematics</option>
            <option value="physics">Physics</option>
        </select>
        <label for="student_class">Class</label>
        <select name="student_class" id="student_class" class="select" required>
            <option value="JSS1">JSS1</option>
            <option value="JSS2">JSS2</option>
            <option value="JSS3">JSS3</option>
            <option value="SS1">SS1</option>
            <option value="SS2">SS2</option>
            <option value="SS3" selected>SS3</option>
        </select>
        <button class="button" type="submit">Continue to Login</button>
    </form>
</div>

<?php loadPartial('end') ?>