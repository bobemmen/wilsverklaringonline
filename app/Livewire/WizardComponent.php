<?php

namespace App\Livewire;

use App\Models\Declaration;
use App\Models\DeclarationVersion;
use App\Support\DeclarationContent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

class WizardComponent extends Component
{
    public int $step = 1;
    public int $totalSteps = 14;
    public array $data = [];
    public ?string $statusMessage = null;

    public array $chapters = [
        1 => ['title' => 'Introductie', 'chapter' => 1],
        2 => ['title' => 'Introductie', 'chapter' => 1],
        3 => ['title' => 'Behandelverbod', 'chapter' => 2],
        4 => ['title' => 'Behandelverbod', 'chapter' => 2],
        5 => ['title' => 'Behandelverbod', 'chapter' => 2],
        6 => ['title' => 'Palliatieve sedatie', 'chapter' => 3],
        7 => ['title' => 'Palliatieve sedatie', 'chapter' => 3],
        8 => ['title' => 'Euthanasieverzoek', 'chapter' => 4],
        9 => ['title' => 'Euthanasieverzoek', 'chapter' => 4],
        10 => ['title' => 'Euthanasieverzoek', 'chapter' => 4],
        11 => ['title' => 'Euthanasieverzoek', 'chapter' => 4],
        12 => ['title' => 'Behandelverbod', 'chapter' => 2],
        13 => ['title' => 'Afsluiting', 'chapter' => 5],
        14 => ['title' => 'Afsluiting', 'chapter' => 5],
    ];

    public function mount(): void
    {
        $declaration = $this->declaration();

        $draft = session('wizard_draft');
        if (is_array($draft)) {
            $this->data = DeclarationContent::normalize($draft);
        } elseif ($declaration->currentVersion) {
            $this->data = DeclarationContent::normalize($declaration->currentVersion->content);
        } else {
            $this->data = DeclarationContent::defaults();
        }
    }

    protected function declaration(): Declaration
    {
        return Declaration::firstOrCreate([
            'user_id' => Auth::id(),
        ], [
            'is_published' => false,
        ]);
    }

    protected function persistDraft(): void
    {
        session(['wizard_draft' => $this->data]);
    }

    public function next(): void
    {
        $this->persistDraft();
        if ($this->step === 8 && ($this->data['euthanasie_opgenomen'] === false || $this->data['euthanasie_opgenomen'] === 'nee')) {
            $this->step = 12;
            return;
        }
        if ($this->step < $this->totalSteps) {
            $this->step++;
        }
    }

    public function previous(): void
    {
        $this->persistDraft();
        if ($this->step === 12 && ($this->data['euthanasie_opgenomen'] === false || $this->data['euthanasie_opgenomen'] === 'nee')) {
            $this->step = 8;
            return;
        }
        if ($this->step > 1) {
            $this->step--;
        }
    }

    public function skip(): void
    {
        $this->persistDraft();
        if ($this->step === 8) {
            $this->data['euthanasie_opgenomen'] = 'nee';
            $this->step = 12;
            return;
        }
        $this->next();
    }

    public function goToStep(int $step): void
    {
        $this->persistDraft();
        if ($step < 1 || $step > $this->totalSteps) {
            return;
        }
        $this->step = $step;
    }

    public function saveDraft(): void
    {
        $this->persistDraft();
        $this->statusMessage = 'Concept opgeslagen.';
    }

    public function publish(): void
    {
        $this->persistDraft();

        DB::transaction(function () {
            $declaration = $this->declaration();

            $version = DeclarationVersion::create([
                'declaration_id' => $declaration->id,
                'content' => $this->data,
            ]);

            $declaration->current_version_id = $version->id;
            $declaration->is_published = true;
            $declaration->save();
        });

        session()->forget('wizard_draft');
        $this->statusMessage = 'Uw wilsverklaring is gepubliceerd.';

        $this->redirectRoute('dashboard', navigate: false);
    }

    public function render()
    {
        return view('livewire.wizard-component', [
            'chapter' => $this->chapters[$this->step]['chapter'],
            'chapterTitle' => $this->chapters[$this->step]['title'],
        ]);
    }
}
