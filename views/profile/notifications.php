<?php
// views/profile/notifications.php
?>
<main class="flex h-full grow flex-col">
    <div class="glass-page-wrapper">
        <div class="w-full pt-[180px] pb-16 px-4">
            <div class="mx-auto max-w-4xl">
                <div class="mb-8 flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-white"><?php _e('profile_page.notifications_title'); ?></h1>
                        <p class="text-white/60"><?php _e('profile_page.notifications_subtitle'); ?></p>
                    </div>
                    <a href="<?php echo prettyUrl('index.php'); ?>" class="flex items-center gap-2 text-white/70 hover:text-accent transition-colors">
                        <span class="material-symbols-outlined">arrow_back</span>
                        <?php _e('common.back'); ?>
                    </a>
                </div>

                <div class="glass-card overflow-hidden">
                    <?php if (empty($notifications)): ?>
                        <div class="flex flex-col items-center justify-center py-20 text-white/40">
                            <span class="material-symbols-outlined text-6xl mb-4 opacity-20">notifications_off</span>
                            <p><?php _e('profile_page.no_notifications'); ?></p>
                        </div>
                    <?php else: ?>
                        <div class="divide-y divide-white/5">
                            <?php foreach ($notifications as $n): ?>
                                <div class="p-6 hover:bg-white/5 transition-all group <?php echo !$n['is_read'] ? 'bg-accent/5' : ''; ?>">
                                    <div class="flex gap-4">
                                        <div class="w-10 h-10 rounded-full bg-accent/10 flex items-center justify-center text-accent shrink-0">
                                            <span class="material-symbols-outlined"><?php echo $n['icon'] ?? 'info'; ?></span>
                                        </div>
                                        <div class="grow">
                                            <div class="flex justify-between items-start mb-1">
                                                <h4 class="font-bold text-white"><?php echo htmlspecialchars($n['title']); ?></h4>
                                                <span class="text-xs text-white/40"><?php echo date('m/d/Y H:i', strtotime($n['created_at'])); ?></span>
                                            </div>
                                            <p class="text-sm text-white/70 leading-relaxed"><?php echo htmlspecialchars($n['message']); ?></p>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>
