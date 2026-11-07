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

.account-page { padding: 2rem 1rem; max-width: 900px; margin: 0 auto; }

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

.profile-card {
    background: #fff;
    border-radius: 18px;
    border: 1px solid var(--border);
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.04);
}

.profile-banner {
    background: linear-gradient(135deg, var(--brand-soft), #fff);
    padding: 2.25rem 2rem 1.5rem;
    display: flex;
    align-items: center;
    gap: 1.5rem;
    border-bottom: 1px solid var(--border);
}

.profile-avatar {
    width: 96px; height: 96px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.4rem;
    font-weight: 700;
    box-shadow: 0 6px 18px rgba(var(--brand-rgb),0.25);
    flex-shrink: 0;
}

.profile-headline h2 { margin: 0 0 0.2rem; font-size: 1.4rem; font-weight: 700; color: var(--text-primary); }
.profile-headline .role-pill {
    display: inline-block;
    padding: 0.2rem 0.7rem;
    background: rgba(var(--brand-rgb),0.12);
    color: var(--primary);
    border-radius: 999px;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.4px;
}
.profile-headline .dept { color: var(--text-secondary); font-size: 0.85rem; margin-left: 0.4rem; }

.profile-grid {
    padding: 1.75rem 2rem 2rem;
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 1.25rem 2rem;
}

.profile-field { padding-bottom: 0.75rem; border-bottom: 1px dashed var(--border); }
.profile-field .label {
    color: var(--text-secondary);
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    margin-bottom: 0.35rem;
    display: flex; align-items: center; gap: 0.4rem;
}
.profile-field .label i { color: var(--primary); }
.profile-field .value {
    color: var(--text-primary);
    font-weight: 600;
    font-size: 0.95rem;
    word-break: break-word;
}
.profile-field .value.muted { color: var(--text-secondary); font-style: italic; font-weight: 500; }

.profile-actions {
    padding: 1rem 2rem 1.5rem;
    border-top: 1px solid var(--border);
    background: #fafafa;
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
}
.btn-account {
    background: var(--primary);
    color: #fff;
    padding: 0.6rem 1.25rem;
    border-radius: 10px;
    text-decoration: none;
    font-weight: 600;
    font-size: 0.85rem;
    transition: all 0.2s ease;
    display: inline-flex; align-items: center; gap: 0.5rem;
}
.btn-account:hover { background: var(--primary-dark); color: #fff; text-decoration: none; transform: translateY(-1px); }

.btn-account-outline {
    background: #fff;
    color: var(--primary);
    border: 2px solid var(--primary);
    padding: 0.5rem 1.1rem;
    border-radius: 10px;
    text-decoration: none;
    font-weight: 600;
    font-size: 0.85rem;
    transition: all 0.2s ease;
    display: inline-flex; align-items: center; gap: 0.5rem;
}
.btn-account-outline:hover { background: var(--brand-soft); color: var(--primary); text-decoration: none; }

@media (max-width: 640px) {
    .profile-grid { grid-template-columns: 1fr; padding: 1.25rem 1.25rem 1.5rem; }
    .profile-banner { flex-direction: column; text-align: center; padding: 1.75rem 1.25rem; }
    .profile-avatar { width: 80px; height: 80px; font-size: 2rem; }
}
</style>

<?php
$initials = strtoupper(substr($user->fullname ?? 'U', 0, 1));
$parts = preg_split('/\s+/', trim($user->fullname ?? ''));
if (count($parts) >= 2) {
    $initials = strtoupper(substr($parts[0], 0, 1) . substr($parts[count($parts)-1], 0, 1));
}
?>

<div class="account-page">

    <div class="page-header">
        <h1><i class="fas fa-user-circle me-2"></i><?= lang_line('acc_profile_title'); ?></h1>
        <p><?= lang_line('acc_profile_subtitle'); ?></p>
    </div>

    <div class="profile-card">

        <div class="profile-banner">
            <div class="profile-avatar"><?= html_escape($initials); ?></div>
            <div class="profile-headline">
                <h2><?= html_escape($user->fullname ?? '-'); ?></h2>
                <span class="role-pill"><?= html_escape($user->role_name ?? lang_line('acc_no_role')); ?></span>
                <?php if (!empty($user->department_name)): ?>
                    <span class="dept">• <?= html_escape($user->department_name); ?></span>
                <?php endif; ?>
            </div>
        </div>

        <div class="profile-grid">

            <div class="profile-field">
                <div class="label"><i class="fas fa-user"></i> <?= lang_line('acc_field_fullname'); ?></div>
                <div class="value"><?= html_escape($user->fullname ?? '-'); ?></div>
            </div>

            <div class="profile-field">
                <div class="label"><i class="fas fa-at"></i> <?= lang_line('acc_field_username'); ?></div>
                <div class="value"><?= html_escape($user->username ?? '-'); ?></div>
            </div>

            <div class="profile-field">
                <div class="label"><i class="fas fa-envelope"></i> <?= lang_line('acc_field_email'); ?></div>
                <div class="value <?= empty($user->email) ? 'muted' : ''; ?>">
                    <?= !empty($user->email) ? html_escape($user->email) : lang_line('acc_not_set'); ?>
                </div>
            </div>

            <div class="profile-field">
                <div class="label"><i class="fas fa-id-badge"></i> <?= lang_line('acc_field_role'); ?></div>
                <div class="value"><?= html_escape($user->role_name ?? '-'); ?></div>
            </div>

            <div class="profile-field">
                <div class="label"><i class="fas fa-building"></i> <?= lang_line('acc_field_department'); ?></div>
                <div class="value <?= empty($user->department_name) ? 'muted' : ''; ?>">
                    <?= !empty($user->department_name) ? html_escape($user->department_name) : lang_line('acc_not_set'); ?>
                </div>
            </div>

            <div class="profile-field">
                <div class="label"><i class="fas fa-calendar-plus"></i> <?= lang_line('acc_field_join_date'); ?></div>
                <div class="value <?= empty($user->tanggal_masuk) ? 'muted' : ''; ?>">
                    <?= !empty($user->tanggal_masuk) ? date('d F Y', strtotime($user->tanggal_masuk)) : lang_line('acc_not_set'); ?>
                </div>
            </div>

            <div class="profile-field">
                <div class="label"><i class="fas fa-clock"></i> <?= lang_line('acc_field_last_login'); ?></div>
                <div class="value <?= empty($user->last_login) ? 'muted' : ''; ?>">
                    <?= !empty($user->last_login) ? date('d F Y, H:i', strtotime($user->last_login)) : '-'; ?>
                </div>
            </div>

            <div class="profile-field">
                <div class="label"><i class="fas fa-user-plus"></i> <?= lang_line('acc_field_created_at'); ?></div>
                <div class="value">
                    <?= !empty($user->created_at) ? date('d F Y', strtotime($user->created_at)) : '-'; ?>
                </div>
            </div>

        </div>

        <div class="profile-actions">
            <a href="<?= base_url('account/settings'); ?>" class="btn-account">
                <i class="fas fa-cog"></i> <?= lang_line('acc_btn_settings'); ?>
            </a>
            <a href="<?= base_url('dashboard'); ?>" class="btn-account-outline">
                <i class="fas fa-arrow-left"></i> <?= lang_line('acc_btn_back_dashboard'); ?>
            </a>
        </div>

    </div>

</div>
