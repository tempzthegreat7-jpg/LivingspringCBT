<?php loadPartial('admin-head') ?>
<?php loadPartial('admin-sidebar') ?>

<section class="admin-content">
    <?php loadPartial('admin-header') ?>
    <main>
        <div class="dashboard-head">
            <h1>Future Plans</h1>
            <p>Track upcoming improvements and roadmap items. Add a plan, then mark it resolved when complete.</p>
        </div>

        <?php if ($message = Session::getFlashMesssge('success_message')): ?>
            <div class="success-message"><?= htmlspecialchars((string) $message, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <?php if ($message = Session::getFlashMesssge('error_message')): ?>
            <div class="errors"><?= htmlspecialchars((string) $message, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <div class="admin-grid create-mode future-plans-grid">
            <article class="form-card">
                <h3>New Plan</h3>
                <form id="future-plan-form" action="/admin/future-plans" method="POST">
                    <?= csrfField() ?>
                    <label for="plan_text">Plan details</label>
                    <textarea
                        id="plan_text"
                        name="plan_text"
                        class="select future-plan-textarea"
                        rows="6"
                        placeholder="e.g. Add offline mode for student exams..."
                        required
                    ></textarea>
                    <button class="button" type="submit">Save Plan</button>
                </form>
            </article>

            <article class="table-card future-plans-feed-card">
                <div class="table-head">
                    <h3>All Plans</h3>
                    <p><?= count($plans ?? []) ?> item<?= count($plans ?? []) === 1 ? '' : 's' ?></p>
                </div>
                <div class="future-plans-feed-list">
                    <?php if (!empty($plans ?? [])): ?>
                        <?php foreach (($plans ?? []) as $plan): ?>
                            <?php
                                $planId = (int) ($plan['id'] ?? 0);
                                $planText = trim((string) ($plan['plan_text'] ?? ''));
                                $isResolved = (int) ($plan['is_resolved'] ?? 0) === 1;
                                $resolvedAt = trim((string) ($plan['resolved_at'] ?? ''));
                                $createdAt = trim((string) ($plan['created_at'] ?? ''));
                            ?>
                            <div class="future-plan-card <?= $isResolved ? 'resolved' : 'open' ?>" data-plan-id="<?= $planId ?>">
                                <div class="future-plan-head">
                                    <div class="future-plan-meta">
                                        <span class="future-plan-id">#<?= htmlspecialchars(displayUserId($planId), ENT_QUOTES, 'UTF-8') ?></span>
                                        <span class="future-plan-date"><?= htmlspecialchars(date('M j, Y g:i A', strtotime($createdAt ?: 'now')), ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                    <span class="future-plan-status-pill <?= $isResolved ? 'resolved' : 'open' ?>">
                                        <?= $isResolved ? 'Resolved' : 'Open' ?>
                                    </span>
                                </div>
                                <p class="future-plan-text"><?= htmlspecialchars($planText, ENT_QUOTES, 'UTF-8') ?></p>
                                <?php if ($isResolved && $resolvedAt !== ''): ?>
                                    <small class="future-plan-resolved-at">
                                        Resolved on <?= htmlspecialchars(date('M j, Y g:i A', strtotime($resolvedAt)), ENT_QUOTES, 'UTF-8') ?>
                                    </small>
                                <?php endif; ?>
                                <div class="future-plan-actions">
                                    <form action="/admin/future-plans/resolve" method="POST" class="future-plan-resolve-form">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="plan_id" value="<?= $planId ?>" />
                                        <input type="hidden" name="action" value="<?= $isResolved ? 'unresolve' : 'resolve' ?>" />
                                        <button type="submit" class="mini-btn <?= $isResolved ? 'ghost' : '' ?>">
                                            <?= $isResolved ? 'Reopen' : 'Resolve' ?>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="future-plans-empty">
                            <h4>No plans yet</h4>
                            <p>Add your first future plan to get started.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </article>
        </div>
    </main>
</section>

<script>
    (function() {
        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        const csrfToken = csrfMeta ? String(csrfMeta.getAttribute('content') || '') : '';
        const form = document.getElementById('future-plan-form');
        const textarea = document.getElementById('plan_text');

        if (form && textarea) {
            form.addEventListener('submit', function(event) {
                event.preventDefault();

                const planText = textarea.value.trim();
                if (planText === '') {
                    return;
                }

                const formData = new FormData();
                formData.append('plan_text', planText);
                formData.append('_token', csrfToken);

                fetch(form.getAttribute('action') || '/admin/future-plans', {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-Token': csrfToken
                        },
                        body: formData
                    })
                    .then(function(response) {
                        return response.json().catch(() => null).then((payload) => ({
                            ok: response.ok,
                            payload: payload
                        }));
                    })
                    .then(function(result) {
                        if (!result.ok) {
                            const message = (result.payload && result.payload.message) ? result.payload.message : 'Unable to save plan.';
                            if (window.AppWarning && typeof window.AppWarning.alert === 'function') {
                                window.AppWarning.alert(message);
                            }
                            return;
                        }

                        textarea.value = '';
                        if (window.AppWarning && typeof window.AppWarning.alert === 'function') {
                            window.AppWarning.alert('Plan saved.', { title: 'Success', variant: 'success' });
                        }

                        window.location.reload();
                    })
                    .catch(function() {
                        if (window.AppWarning && typeof window.AppWarning.alert === 'function') {
                            window.AppWarning.alert('Unable to save plan right now.');
                        }
                    });
            });
        }
    })();
</script>

<?php loadPartial('end') ?>
