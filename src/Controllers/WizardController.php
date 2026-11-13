<?php

declare(strict_types=1);

namespace WV\Controllers;

use WV\Content;
use WV\Response;

final class WizardController extends Controller
{
    /** Huidige werkversie: concept, anders de gepubliceerde versie, anders leeg. */
    private function workingData(array $declaration): array
    {
        if (is_array($declaration['draft'])) {
            return Content::normalize($declaration['draft']);
        }
        $version = $this->app->store->version($declaration['current_version_id'], $declaration['id']);
        return $version ? Content::normalize($version['content']) : Content::defaults();
    }

    public function show(): Response
    {
        $step = (int) $this->input('stap');
        $step = ($step >= 1 && $step <= Content::TOTAL_STEPS) ? $step : 1;
        $declaration = $this->declaration();

        $chapter = Content::STEP_CHAPTER[$step];
        $chapterTitle = Content::CHAPTER_NAMES[$chapter];
        return $this->view('wizard/show', [
            'step' => $step,
            'total' => Content::TOTAL_STEPS,
            'chapter' => $chapter,
            'chapterTitle' => $chapterTitle,
            'stepTitle' => Content::TITLES[$step],
            'data' => $this->workingData($declaration),
        ]);
    }

    public function save(): Response
    {
        $step = (int) $this->input('stap');
        if ($step < 1 || $step > Content::TOTAL_STEPS) {
            return $this->redirect('/verklaring/wizard');
        }
        $action = $this->input('actie');
        $declaration = $this->declaration();

        $data = Content::mergeStep($step, $this->app->request->inputArray('data'), $this->workingData($declaration));

        if ($action === 'overslaan' && isset(Content::GATES[$step])) {
            $data[Content::GATES[$step][0]] = 'nee';
        }

        if ($action === 'publiceer' && $step === Content::TOTAL_STEPS) {
            $missing = Content::missingForPublish(Content::normalize($data));
            if ($missing) {
                $declaration['draft'] = $data;
                $this->app->store->saveDeclaration($declaration);
                $errors = [];
                foreach ($missing as $i => $text) {
                    $errors['ontbreekt-' . $i] = $text;
                }
                return $this->fail('/verklaring/wizard?stap=17', $errors);
            }
            $this->app->db->transaction(function () use (&$declaration, $data) {
                $version = $this->app->store->addVersion($declaration['id'], Content::normalize($data));
                $declaration['current_version_id'] = $version['id'];
                $declaration['is_published'] = true;
                $declaration['draft'] = null;
                $this->app->store->saveDeclaration($declaration);
            });
            $this->flash('Uw wilsverklaring is gepubliceerd.');
            return $this->redirect('/dashboard');
        }

        $declaration['draft'] = $data;
        $this->app->store->saveDeclaration($declaration);

        $target = match ($action) {
            'terug' => Content::previous($step, $data),
            'volgende' => Content::next($step, $data),
            'overslaan' => Content::GATES[$step][1] ?? Content::next($step, $data),
            default => $step,
        };
        if ($action === 'concept') {
            $this->flash('Concept opgeslagen.');
        }
        return $this->redirect('/verklaring/wizard?stap=' . $target);
    }

    public function versions(): Response
    {
        $declaration = $this->declaration();
        return $this->view('pages/versions', [
            'declaration' => $declaration,
            'versions' => array_reverse($this->app->store->versions($declaration['id'])), // nieuwste eerst
        ]);
    }
}
