<?php $this->layout('layouts/app', ['title' => 'Versies · WilsVerklaring']) ?>
<h1>Versies van uw verklaring</h1>
<p class="muted small" style="margin-bottom: 18px;">Elke publicatie is een nieuwe versie. Eerdere versies blijven bewaard zodat u kunt terugzien wat wanneer gold. Uw arts ziet altijd de meest recente versie.</p>

<div class="card">
    <?php if (!$versions): ?>
        <p class="muted">Er zijn nog geen gepubliceerde versies.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr><th>Versie</th><th>Gepubliceerd op</th><th>Status</th></tr>
            </thead>
            <tbody>
                <?php foreach ($versions as $i => $version): ?>
                    <tr>
                        <td>v<?= count($versions) - $i ?></td>
                        <td><?= e(fmt_datetime($version['created_at'])) ?></td>
                        <td>
                            <?php if ($version['id'] === $declaration['current_version_id']): ?>
                                <span class="pill pill-green">Huidig</span>
                            <?php else: ?>
                                <span class="pill pill-muted">Archief</span>
                            <?php endif ?>
                        </td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    <?php endif ?>
</div>
