<?php $this->layout('layouts/app', ['title' => 'Versies · WilsVerklaring']) ?>
<div class="page-head">
    <h1>Versies van uw verklaring</h1>
    <p>Elke publicatie is een nieuwe versie. Oudere versies blijven bewaard, zodat u kunt terugzien wat wanneer gold. Uw arts ziet altijd de nieuwste.</p>
</div>

<div class="card">
    <?php if (!$versions): ?>
        <p class="mb0">Er zijn nog geen gepubliceerde versies.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <caption class="sr-only">Gepubliceerde versies, nieuwste eerst</caption>
                <thead><tr><th scope="col">Versie</th><th scope="col">Gepubliceerd op</th><th scope="col">Status</th><th scope="col">Bekijken</th></tr></thead>
                <tbody>
                    <?php foreach ($versions as $i => $version): ?>
                        <tr>
                            <?php $nr = count($versions) - $i ?>
                            <th scope="row"><a href="/verklaring/versies/<?= $nr ?>">Versie <?= $nr ?></a></th>
                            <td><?= e(fmt_datetime($version['created_at'])) ?></td>
                            <td>
                                <?php if ($version['id'] === $declaration['current_version_id']): ?>
                                    <span class="badge badge-ok">Huidig</span>
                                <?php else: ?>
                                    <span class="badge badge-muted">Archief</span>
                                <?php endif ?>
                            </td>
                            <td>
                                <a href="/verklaring/versies/<?= $nr ?>">Lezen<span class="sr-only"> versie <?= $nr ?></span></a>
                                <?php if ($version['id'] === $declaration['current_version_id']): ?>
                                    · <a href="/verklaring/versies/<?= $nr ?>/pdf">PDF<span class="sr-only"> van versie <?= $nr ?></span></a>
                                <?php endif ?>
                            </td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>
    <?php endif ?>
</div>
