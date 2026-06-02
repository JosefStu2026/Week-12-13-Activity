<?= $this->extend('layouts/master') ?>

<?= $this->section('content') ?>
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">Create Profile Securely</h5>
            </div>
            <div class="card-body">

                <?php if (session()->getFlashdata('errors')): ?>
                    <div class="alert alert-danger">
                        <?php foreach (session()->getFlashdata('errors') as $error): ?>
                            <li><?= esc($error) ?></li>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form action="<?= site_url('users/store') ?>" method="post" enctype="multipart/form-data">
                    <?= csrf_field() ?> <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="name" class="form-control" value="<?= old('name') ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control" value="<?= old('email') ?>">
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Profile Image (PNG/JPG up to 2MB)</label>
                        <input type="file" name="avatar" class="form-control">
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="<?= site_url('users') ?>" class="btn btn-light">Back</a>
                        <button type="submit" class="btn btn-success">Save Operational Profile</button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>