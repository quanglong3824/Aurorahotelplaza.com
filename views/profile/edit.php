<?php
// views/profile/edit.php
?>
<main class="flex h-full grow flex-col">
    <div class="glass-page-wrapper">
        <div class="w-full pt-[180px] pb-16 px-4">
            <div class="mx-auto max-w-4xl">
                <div class="mb-8 flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-white"><?php _e('profile_page.edit_title'); ?></h1>
                        <p class="text-white/60"><?php _e('profile_page.edit_subtitle'); ?></p>
                    </div>
                    <a href="<?php echo prettyUrl('index.php'); ?>" class="flex items-center gap-2 text-white/70 hover:text-accent transition-colors">
                        <span class="material-symbols-outlined">arrow_back</span>
                        <?php _e('common.back'); ?>
                    </a>
                </div>

                <div class="glass-card p-8">
                    <?php if (!empty($success)): ?>
                        <div class="mb-6 p-4 bg-green-500/20 border border-green-500/40 rounded-xl text-green-400 flex items-center gap-3">
                            <span class="material-symbols-outlined">check_circle</span>
                            <?php echo $success; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($error)): ?>
                        <div class="mb-6 p-4 bg-red-500/20 border border-red-500/40 rounded-xl text-red-400 flex items-center gap-3">
                            <span class="material-symbols-outlined">error</span>
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Full Name -->
                            <div class="space-y-2">
                                <label class="text-sm font-medium text-white/70 ml-1"><?php _e('profile_page.full_name'); ?></label>
                                <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required
                                    class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-accent focus:bg-white/10 transition-all">
                            </div>

                            <!-- Phone -->
                            <div class="space-y-2">
                                <label class="text-sm font-medium text-white/70 ml-1"><?php _e('profile_page.phone'); ?></label>
                                <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"
                                    class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-accent focus:bg-white/10 transition-all">
                            </div>

                            <!-- Date of Birth -->
                            <div class="space-y-2">
                                <label class="text-sm font-medium text-white/70 ml-1"><?php _e('profile_page.dob'); ?></label>
                                <input type="date" name="date_of_birth" value="<?php echo $user['date_of_birth']; ?>"
                                    class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-accent focus:bg-white/10 transition-all [color-scheme:dark]">
                            </div>

                            <!-- Gender -->
                            <div class="space-y-2">
                                <label class="text-sm font-medium text-white/70 ml-1"><?php _e('profile_page.gender'); ?></label>
                                <select name="gender" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-accent focus:bg-white/10 transition-all">
                                    <option value="" class="bg-[#1a1a1a]"><?php _e('profile_page.gender_select'); ?></option>
                                    <option value="male" <?php echo ($user['gender'] == 'male' ? 'selected' : ''); ?> class="bg-[#1a1a1a]"><?php _e('profile_page.gender_male'); ?></option>
                                    <option value="female" <?php echo ($user['gender'] == 'female' ? 'selected' : ''); ?> class="bg-[#1a1a1a]"><?php _e('profile_page.gender_female'); ?></option>
                                    <option value="other" <?php echo ($user['gender'] == 'other' ? 'selected' : ''); ?> class="bg-[#1a1a1a]"><?php _e('profile_page.gender_other'); ?></option>
                                </select>
                            </div>
                        </div>

                        <!-- Address -->
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-white/70 ml-1"><?php _e('profile_page.address'); ?></label>
                            <input type="text" name="address" value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>"
                                class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-accent focus:bg-white/10 transition-all">
                        </div>

                        <div class="pt-6 border-t border-white/10">
                            <h3 class="text-lg font-bold text-white mb-4"><?php _e('profile_page.change_password'); ?></h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-2">
                                    <label class="text-sm font-medium text-white/70 ml-1"><?php _e('profile_page.current_password'); ?></label>
                                    <input type="password" name="current_password"
                                        class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-accent focus:bg-white/10 transition-all">
                                </div>
                                <div class="space-y-2">
                                    <label class="text-sm font-medium text-white/70 ml-1"><?php _e('profile_page.new_password'); ?></label>
                                    <input type="password" name="new_password"
                                        class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-accent focus:bg-white/10 transition-all">
                                </div>
                                <div class="space-y-2">
                                    <label class="text-sm font-medium text-white/70 ml-1"><?php _e('profile_page.confirm_password'); ?></label>
                                    <input type="password" name="confirm_password"
                                        class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-accent focus:bg-white/10 transition-all">
                                </div>
                            </div>
                        </div>

                        <div class="pt-6 flex justify-end gap-4">
                            <button type="submit" class="btn-glass-gold px-8 py-3 font-bold rounded-xl transition-all shadow-lg hover:shadow-accent/20">
                                <?php _e('profile_page.save_changes'); ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>
