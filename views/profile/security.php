<?php
// views/profile/security.php
?>
<main class="flex h-full grow flex-col">
    <div class="glass-page-wrapper">
        <div class="w-full pt-[180px] pb-16 px-4">
            <div class="mx-auto max-w-4xl">
                <div class="mb-8 flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-white"><?php _e('profile_page.security_title'); ?></h1>
                        <p class="text-white/60"><?php _e('profile_page.security_subtitle'); ?></p>
                    </div>
                    <a href="index.php" class="flex items-center gap-2 text-white/70 hover:text-accent transition-colors">
                        <span class="material-symbols-outlined">arrow_back</span>
                        <?php _e('common.back'); ?>
                    </a>
                </div>

                <div class="grid grid-cols-1 gap-8">
                    <!-- Change Password Card -->
                    <div class="glass-card p-8">
                        <h3 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
                            <span class="material-symbols-outlined text-accent">lock</span>
                            <?php _e('profile_page.change_password'); ?>
                        </h3>
                        
                        <form id="changePasswordForm" class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-2">
                                    <label class="text-sm font-medium text-white/70 ml-1"><?php _e('profile_page.current_password'); ?></label>
                                    <input type="password" name="current_password" required
                                        class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-accent focus:bg-white/10 transition-all">
                                </div>
                                <div class="space-y-2">
                                    <label class="text-sm font-medium text-white/70 ml-1"><?php _e('profile_page.new_password'); ?></label>
                                    <input type="password" name="new_password" required
                                        class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-accent focus:bg-white/10 transition-all">
                                </div>
                                <div class="space-y-2">
                                    <label class="text-sm font-medium text-white/70 ml-1"><?php _e('profile_page.confirm_password'); ?></label>
                                    <input type="password" name="confirm_password" required
                                        class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-accent focus:bg-white/10 transition-all">
                                </div>
                            </div>
                            <div class="flex justify-end">
                                <button type="submit" class="btn-glass-gold px-8 py-3 font-bold rounded-xl transition-all shadow-lg hover:shadow-accent/20">
                                    <?php _e('profile_page.update_password'); ?>
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Account Deletion Card -->
                    <div class="glass-card p-8 border-red-500/20 bg-red-500/5">
                        <h3 class="text-xl font-bold text-red-400 mb-6 flex items-center gap-2">
                            <span class="material-symbols-outlined">delete_forever</span>
                            <?php _e('profile_page.delete_account'); ?>
                        </h3>
                        <p class="text-white/60 mb-6">
                            <?php _e('profile_page.delete_account_warning'); ?>
                        </p>
                        <div class="flex justify-end">
                            <button onclick="confirmDeleteAccount()" class="px-6 py-3 bg-red-500/20 hover:bg-red-500/40 text-red-400 border border-red-500/40 font-bold rounded-xl transition-all">
                                <?php _e('profile_page.delete_account_btn'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
document.getElementById('changePasswordForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const data = Object.fromEntries(formData.entries());

    if (data.new_password !== data.confirm_password) {
        alert("<?php _e('profile_page.password_mismatch'); ?>");
        return;
    }

    try {
        const response = await fetch('api/change-password.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });
        const result = await response.json();
        if (result.success) {
            alert(result.message);
            this.reset();
        } else {
            alert(result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    }
});

async function confirmDeleteAccount() {
    if (confirm("<?php _e('profile_page.delete_account_confirm'); ?>")) {
        try {
            const response = await fetch('api/delete-account.php', {
                method: 'POST'
            });
            const result = await response.json();
            if (result.success) {
                window.location.href = '../auth/logout.php';
            } else {
                alert(result.message);
            }
        } catch (error) {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        }
    }
}
</script>
