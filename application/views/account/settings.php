<style>
:root {
    --primary: var(--brand-1);
    --primary-dark: var(--brand-dark);
    --light-bg: #F9FAFB;
    --card-bg: #FFFFFF;
    --text-primary: #111827;
    --text-secondary: #6B7280;
    --border: #E5E7EB;
}

body { background: var(--light-bg); }

.settings-page { padding: 2rem 1rem; max-width: 720px; margin: 0 auto; }

.page-header {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    border-radius: 20px;
    padding: 2rem 2.25rem;
    margin-bottom: 1.75rem;
    color: white;
    box-shadow: 0 8px 24px rgba(var(--brand-rgb),0.18);
}
.page-header h1 { font-size: 1.5rem; font-weight: 700; margin: 0 0 0.25rem; }
.page-header p { margin: 0; opacity: 0.92; font-size: 0.9rem; }

.settings-card {
    background: #fff;
    border-radius: 18px;
    border: 1px solid var(--border);
    box-shadow: 0 2px 10px rgba(0,0,0,0.04);
    overflow: hidden;
}

.settings-section-head {
    padding: 1.25rem 1.75rem;
    border-bottom: 1px solid var(--border);
    background: #fafafa;
    display: flex;
    align-items: center;
    gap: 0.6rem;
}
.settings-section-head i { color: var(--primary); font-size: 1.05rem; }
.settings-section-head h3 { margin: 0; font-size: 1rem; font-weight: 700; color: var(--text-primary); }
.settings-section-head .desc { margin-left: auto; font-size: 0.8rem; color: var(--text-secondary); }

.settings-body { padding: 1.75rem; }

.form-group { margin-bottom: 1.25rem; }
.form-group label {
    display: block;
    font-weight: 600;
    color: var(--text-primary);
    font-size: 0.85rem;
    margin-bottom: 0.4rem;
}
.form-group .input-wrapper { position: relative; }
.form-group input[type="password"] {
    width: 100%;
    padding: 0.7rem 2.5rem 0.7rem 0.95rem;
    border: 2px solid var(--border);
    border-radius: 10px;
    font-size: 0.92rem;
    background: #fff;
    transition: all 0.2s ease;
}
.form-group input[type="password"]:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(var(--brand-rgb),0.1);
}

.toggle-pw {
    position: absolute;
    right: 0.55rem;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: var(--text-secondary);
    cursor: pointer;
    padding: 0.4rem 0.5rem;
    border-radius: 6px;
}
.toggle-pw:hover { color: var(--primary); background: rgba(var(--brand-rgb),0.08); }

.help-text { font-size: 0.75rem; color: var(--text-secondary); margin-top: 0.35rem; }

.alert-box {
    padding: 0.75rem 1rem;
    border-radius: 10px;
    margin-bottom: 1.25rem;
    font-size: 0.88rem;
    display: flex;
    align-items: center;
    gap: 0.6rem;
}
.alert-success { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
.alert-danger { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }

.form-actions {
    padding: 1.25rem 1.75rem;
    border-top: 1px solid var(--border);
    background: #fafafa;
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
    justify-content: flex-end;
}

.btn-submit {
    background: var(--primary);
    color: #fff;
    border: none;
    padding: 0.65rem 1.5rem;
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.2s ease;
    display: inline-flex; align-items: center; gap: 0.5rem;
}
.btn-submit:hover { background: var(--primary-dark); transform: translateY(-1px); }

.btn-cancel {
    background: #fff;
    color: var(--text-primary);
    border: 2px solid var(--border);
    padding: 0.55rem 1.4rem;
    border-radius: 10px;
    text-decoration: none;
    font-weight: 600;
    font-size: 0.9rem;
    transition: all 0.2s ease;
}
.btn-cancel:hover { border-color: var(--primary); color: var(--primary); text-decoration: none; }

.password-meter {
    height: 6px;
    background: #f1f1f1;
    border-radius: 3px;
    margin-top: 0.5rem;
    overflow: hidden;
}
.password-meter > div {
    height: 100%;
    width: 0%;
    transition: all 0.3s ease;
    background: var(--primary);
    border-radius: 3px;
}
.password-meter-label { font-size: 0.72rem; margin-top: 0.25rem; color: var(--text-secondary); }
</style>

<div class="settings-page">

    <div class="page-header">
        <h1><i class="fas fa-cog me-2"></i><?= lang_line('acc_settings_title'); ?></h1>
        <p><?= lang_line('acc_settings_subtitle'); ?></p>
    </div>

    <?php if ($this->session->flashdata('success')): ?>
        <div class="alert-box alert-success">
            <i class="fas fa-check-circle"></i>
            <?= html_escape($this->session->flashdata('success')); ?>
        </div>
    <?php endif; ?>

    <?php if ($this->session->flashdata('error')): ?>
        <div class="alert-box alert-danger">
            <i class="fas fa-exclamation-circle"></i>
            <?= html_escape($this->session->flashdata('error')); ?>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= base_url('account/update_password'); ?>" id="changePasswordForm" autocomplete="off" novalidate>

        <div class="settings-card">

            <div class="settings-section-head">
                <i class="fas fa-key"></i>
                <h3><?= lang_line('acc_section_change_pw'); ?></h3>
                <span class="desc"><?= lang_line('acc_section_change_pw_desc'); ?></span>
            </div>

            <div class="settings-body">

                <div class="form-group">
                    <label for="current_password"><?= lang_line('acc_label_current_pw'); ?></label>
                    <div class="input-wrapper">
                        <input type="password" id="current_password" name="current_password">
                        <button type="button" class="toggle-pw" data-target="current_password" aria-label="<?= lang_line('acc_show_pw'); ?>">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label for="new_password"><?= lang_line('acc_label_new_pw'); ?></label>
                    <div class="input-wrapper">
                        <input type="password" id="new_password" name="new_password">
                        <button type="button" class="toggle-pw" data-target="new_password" aria-label="<?= lang_line('acc_show_pw'); ?>">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="password-meter"><div id="pwMeterFill"></div></div>
                    <div class="password-meter-label" id="pwMeterLabel"><?= lang_line('acc_min_chars'); ?></div>
                </div>

                <div class="form-group">
                    <label for="confirm_password"><?= lang_line('acc_label_confirm_pw'); ?></label>
                    <div class="input-wrapper">
                        <input type="password" id="confirm_password" name="confirm_password">
                        <button type="button" class="toggle-pw" data-target="confirm_password" aria-label="<?= lang_line('acc_show_pw'); ?>">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="help-text" id="matchHint"></div>
                </div>

            </div>

            <div class="form-actions">
                <a href="<?= base_url('account/profile'); ?>" class="btn-cancel"><?= lang_line('acc_btn_cancel'); ?></a>
                <button type="button" id="btnSubmitPassword" class="btn-submit">
                    <i class="fas fa-save"></i> <?= lang_line('acc_btn_save_pw'); ?>
                </button>
            </div>

        </div>

    </form>

</div>

<script>
const I18N_ACC = {
    min_chars:        <?= json_encode(lang_line('acc_min_chars')); ?>,
    very_weak:        <?= json_encode(lang_line('acc_pw_very_weak')); ?>,
    weak:             <?= json_encode(lang_line('acc_pw_weak')); ?>,
    fair:             <?= json_encode(lang_line('acc_pw_fair')); ?>,
    good:             <?= json_encode(lang_line('acc_pw_good')); ?>,
    strong:           <?= json_encode(lang_line('acc_pw_strong')); ?>,
    pw_match:         <?= json_encode(lang_line('acc_pw_match')); ?>,
    pw_not_match:     <?= json_encode(lang_line('acc_pw_not_match')); ?>,
    mismatch_title:   <?= json_encode(lang_line('acc_pw_mismatch_title')); ?>,
    mismatch_text:    <?= json_encode(lang_line('acc_pw_mismatch_text')); ?>,
    processing:       <?= json_encode(lang_line('acc_processing')); ?>,
    success_title:    <?= json_encode(lang_line('acc_success_title')); ?>,
    success_text:     <?= json_encode(lang_line('acc_success_text')); ?>,
    fail_title:       <?= json_encode(lang_line('acc_fail_title')); ?>,
    fail_text:        <?= json_encode(lang_line('acc_fail_text')); ?>,
    err_title:        <?= json_encode(lang_line('acc_err_title')); ?>,
    err_server:       <?= json_encode(lang_line('acc_err_server')); ?>
};
document.addEventListener('DOMContentLoaded', function () {

    // Toggle password visibility
    document.querySelectorAll('.toggle-pw').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var target = document.getElementById(btn.dataset.target);
            var icon = btn.querySelector('i');
            if (!target) return;
            if (target.type === 'password') {
                target.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                target.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    });

    // Strength meter
    var newPw = document.getElementById('new_password');
    var meter = document.getElementById('pwMeterFill');
    var label = document.getElementById('pwMeterLabel');
    if (newPw) {
        newPw.addEventListener('input', function () {
            var v = newPw.value;
            var score = 0;
            if (v.length >= 6) score++;
            if (v.length >= 10) score++;
            if (/[A-Z]/.test(v) && /[a-z]/.test(v)) score++;
            if (/\d/.test(v)) score++;
            if (/[^A-Za-z0-9]/.test(v)) score++;

            var pct = (score / 5) * 100;
            meter.style.width = pct + '%';
            var msg = [I18N_ACC.very_weak, I18N_ACC.weak, I18N_ACC.fair, I18N_ACC.good, I18N_ACC.strong][Math.max(0, score - 1)] || I18N_ACC.min_chars;
            var colors = ['#ef4444', '#f59e0b', '#eab308', '#10b981', '#059669'];
            meter.style.background = colors[Math.max(0, score - 1)] || '#c62828';
            label.textContent = v.length === 0 ? I18N_ACC.min_chars : msg;
        });
    }

    // Match check (use confirmPw to avoid shadowing window.confirm)
    var confirmPw = document.getElementById('confirm_password');
    var hint = document.getElementById('matchHint');
    function checkMatch() {
        if (!confirmPw.value) { hint.textContent = ''; return; }
        if (newPw.value === confirmPw.value) {
            hint.textContent = I18N_ACC.pw_match;
            hint.style.color = '#059669';
        } else {
            hint.textContent = I18N_ACC.pw_not_match;
            hint.style.color = '#ef4444';
        }
    }
    if (confirmPw && newPw) {
        confirmPw.addEventListener('input', checkMatch);
        newPw.addEventListener('input', checkMatch);
    }

    // Submit via AJAX (bypasses any form-submit handler conflicts)
    var btn  = document.getElementById('btnSubmitPassword');
    var form = document.getElementById('changePasswordForm');

    if (btn && form) {
        btn.addEventListener('click', function () {
            // Quick client-side hint (server still validates authoritatively)
            if (confirmPw && newPw && newPw.value !== confirmPw.value) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'error', title: I18N_ACC.mismatch_title, text: I18N_ACC.mismatch_text });
                } else {
                    alert(I18N_ACC.mismatch_text);
                }
                return;
            }

            btn.disabled = true;
            var originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> ' + I18N_ACC.processing;

            var formData = new FormData(form);

            fetch(form.action, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function (res) {
                return res.text().then(function (text) {
                    var json;
                    try { json = JSON.parse(text); } catch (e) { json = null; }
                    return { status: res.status, ok: res.ok, json: json, text: text };
                });
            })
            .then(function (resp) {
                btn.disabled = false;
                btn.innerHTML = originalText;

                if (resp.json && resp.json.status === true) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: I18N_ACC.success_title,
                            text: resp.json.message || I18N_ACC.success_text,
                            timer: 1800,
                            showConfirmButton: false
                        }).then(function () {
                            form.reset();
                            document.getElementById('pwMeterFill').style.width = '0%';
                            document.getElementById('pwMeterLabel').textContent = I18N_ACC.min_chars;
                            if (hint) hint.textContent = '';
                        });
                    } else {
                        alert(resp.json.message || I18N_ACC.success_text);
                        form.reset();
                    }
                } else {
                    var msg = (resp.json && resp.json.message) ? resp.json.message : I18N_ACC.fail_text;
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({ icon: 'error', title: I18N_ACC.fail_title, text: msg });
                    } else {
                        alert(msg);
                    }
                }
            })
            .catch(function (err) {
                btn.disabled = false;
                btn.innerHTML = originalText;
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'error', title: I18N_ACC.err_title, text: I18N_ACC.err_server });
                } else {
                    alert(I18N_ACC.err_server);
                }
                console.error('update_password error:', err);
            });
        });
    }
});
</script>
