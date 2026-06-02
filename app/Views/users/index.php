<?= $this->extend('layouts/master') ?>

<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2>User Base Directory</h2>
    <a href="<?= site_url('users/create') ?>" class="btn btn-primary">Register New User</a>
</div>

<form action="<?= site_url('users') ?>" method="get" class="row g-2 mb-4">
    <div class="col-md-10">
        <input type="text" name="search" class="form-control" placeholder="Search users by name..." value="<?= esc($searchKeyword) ?>">
    </div>
    <div class="col-md-2">
        <button type="submit" class="btn btn-secondary w-100">Search</button>
    </div>
</form>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <table class="table table-hover table-striped mb-0">
            <thead class="table-dark">
                <tr>
                    <th>Avatar</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($users) && is_array($users)): ?>
                    <?php foreach ($users as $user): ?>
                        <tr class="align-middle">
                            <td>
                                <img src="<?= base_url('uploads/' . esc($user['avatar'])) ?>" alt="Profile" class="rounded-circle" style="width: 50px; height: 50px; object-fit: cover;">
                            </td>
                            <td><strong><?= esc($user['name']) ?></strong></td>
                            <td><?= esc($user['email']) ?></td>
                            <td>
                                <a href="<?= site_url('users/delete/' . $user['id']) ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to scrub this user trace?')">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="text-center p-4 text-muted">No operational records found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4 d-flex justify-content-center">
    <?= $pager_links ?>
</div>
<?= $this->endSection() ?>

