(function () {
    const questionCards = Array.from(document.querySelectorAll('.question-card'));
    const quizScoreText = document.getElementById('quizScoreText');
    const confidenceScoreText = document.getElementById('confidenceScoreText');
    const answeredCountText = document.getElementById('answeredCountText');
    const quizScoreInput = document.getElementById('quiz_score');
    const confidenceScoreInput = document.getElementById('confidence_score');
    const quizPayloadInput = document.getElementById('quiz_payload_json');
    const submitBtn = document.getElementById('submitSessionBtn');
    const submitStatusText = document.getElementById('submitStatusText');
    const form = document.getElementById('subjectSessionForm');
    const totalQuestions = window.quizQuestionCount || questionCards.length;

    if (!submitBtn || !form) return;

    const questionStartTimes = {};

    function nowMs() {
        return performance.now();
    }

    function collectQuizData() {
        const payload = [];
        let correctCount = 0;
        let answeredCount = 0;
        let totalResponseTime = 0;

        questionCards.forEach((card) => {
            const questionId = parseInt(card.querySelector('.question-id-input')?.value || '0', 10);
            const correctOption = card.querySelector('.question-correct-input')?.value || '';
            const riskWeight = parseFloat(card.querySelector('.question-risk-weight-input')?.value || '1');
            const responseTimeInput = card.querySelector('.question-response-time-input');
            const checked = card.querySelector('.quiz-option-input:checked');

            let selectedOption = '';
            let isCorrect = 0;
            let responseTime = parseInt(responseTimeInput?.value || '0', 10);

            if (checked) {
                selectedOption = checked.value;
                answeredCount++;

                if (selectedOption === correctOption) {
                    isCorrect = 1;
                    correctCount++;
                }
            }

            totalResponseTime += responseTime;

            payload.push({
                question_id: questionId,
                selected_option: selectedOption,
                correct_option: correctOption,
                is_correct: isCorrect,
                response_time_ms: responseTime,
                risk_weight: riskWeight
            });
        });

        const quizScore = totalQuestions > 0 ? (correctCount / totalQuestions) * 100 : 0;

        let avgResponseTime = answeredCount > 0 ? totalResponseTime / answeredCount : 0;
        if (!isFinite(avgResponseTime)) avgResponseTime = 0;

        let confidenceScore = 100 - Math.min(avgResponseTime / 20, 40) - ((totalQuestions - correctCount) * 5);
        confidenceScore = Math.max(0, Math.min(100, confidenceScore));

        return {
            payload,
            quizScore,
            confidenceScore,
            answeredCount
        };
    }

    function renderQuizStats() {
        const data = collectQuizData();
        quizScoreText.textContent = data.quizScore.toFixed(2);
        confidenceScoreText.textContent = data.confidenceScore.toFixed(2);
        answeredCountText.textContent = `${data.answeredCount} / ${totalQuestions}`;

        quizScoreInput.value = data.quizScore.toFixed(2);
        confidenceScoreInput.value = data.confidenceScore.toFixed(2);
        quizPayloadInput.value = JSON.stringify(data.payload);
    }

    questionCards.forEach((card) => {
        const qid = card.getAttribute('data-question-id');
        questionStartTimes[qid] = nowMs();

        card.querySelectorAll('.quiz-option-input').forEach((radio) => {
            radio.addEventListener('change', function () {
                const questionId = card.getAttribute('data-question-id');
                const responseTimeInput = card.querySelector('.question-response-time-input');

                if (responseTimeInput && questionStartTimes[questionId]) {
                    const elapsed = Math.round(nowMs() - questionStartTimes[questionId]);
                    if (!responseTimeInput.value || parseInt(responseTimeInput.value, 10) === 0) {
                        responseTimeInput.value = String(elapsed);
                    }
                }

                renderQuizStats();
            });
        });
    });

    async function submitSession() {
        const subjectId = document.getElementById('subject_id')?.value || '';
        const textSample = document.getElementById('text_sample')?.value.trim() || '';
        const reactionAvg = document.getElementById('reaction_avg')?.value || '';
        const reactionAttemptsJson = document.getElementById('reaction_attempts_json')?.value || '[]';

        renderQuizStats();

        const quizScore = quizScoreInput.value || '';
        const confidenceScore = confidenceScoreInput.value || '';
        const quizPayloadJson = quizPayloadInput.value || '[]';

        const quizData = JSON.parse(quizPayloadJson || '[]');
        const answeredCount = quizData.filter(item => item.selected_option).length;

        let reactionAttempts = [];
        try {
            reactionAttempts = JSON.parse(reactionAttemptsJson || '[]');
        } catch (e) {
            reactionAttempts = [];
        }

        if (!subjectId) {
            submitStatusText.textContent = 'Please select a subject.';
            alert('Please select a subject.');
            return;
        }

        if (!textSample) {
            submitStatusText.textContent = 'Please enter text sample.';
            alert('Please enter text sample.');
            return;
        }

        if (reactionAttempts.length < 3 || !reactionAvg) {
            submitStatusText.textContent = 'Complete at least 3 reaction attempts.';
            alert('Complete at least 3 reaction attempts.');
            return;
        }

        if (answeredCount !== totalQuestions) {
            submitStatusText.textContent = 'Answer all quiz questions.';
            alert('Answer all quiz questions.');
            return;
        }

        submitBtn.disabled = true;
        submitStatusText.textContent = 'Submitting session and running AI analysis...';

        const formData = new FormData();
        formData.append('subject_id', subjectId);
        formData.append('text_sample', textSample);
        formData.append('reaction_avg', reactionAvg);
        formData.append('confidence_score', confidenceScore);
        formData.append('quiz_score', quizScore);
        formData.append('quiz_payload_json', quizPayloadJson);
        formData.append('reaction_attempts_json', reactionAttemptsJson);

        try {
            const response = await fetch(`${window.baseUrl}/api/submit_session.php`, {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                submitStatusText.textContent = 'Session submitted successfully.';
                alert('Session submitted and analyzed successfully.');

                if (result.redirect_url) {
                    window.location.href = result.redirect_url;
                    return;
                }
            } else {
                submitStatusText.textContent = result.message || 'Submit failed.';
                alert(result.message || 'Submit failed.');
                console.error(result);
            }
        } catch (error) {
            console.error(error);
            submitStatusText.textContent = 'Server error during submit.';
            alert('Server error during submit.');
        } finally {
            submitBtn.disabled = false;
        }
    }

    submitBtn.addEventListener('click', submitSession);
    renderQuizStats();
})();