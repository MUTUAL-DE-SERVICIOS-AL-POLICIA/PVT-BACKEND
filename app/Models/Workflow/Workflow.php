<?php

namespace App\Models\Workflow;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Admin\Module;
use App\Models\Contribution\ContributionProcess;

class Workflow extends Model
{
  use HasFactory;
  public $timestamps = false;
  public $guarded = ['id'];
  protected $fillable = [
      'module_id',
      'name',
      'shortened'
  ];

  public function module()
  {
    return $this->belongsTo(Module::class); 
  } 
  public function contribution_processes()
  {
    return $this->hasMany(ContributionProcess::class);
  }
  public function wf_sequences()
  {
    return $this->hasMany(WfSequence::class);
  }

  // Método actualizado para manejar el flujo
  public function flow($current_state_id)
  {
      if (is_null($current_state_id)) {
          throw new \InvalidArgumentException("El parámetro 'current_state_id' es requerido.");
      }
      return [
          'current' => $current_state_id,
          'previous' => $this->wf_sequences()
              ->where('wf_state_next_id', $current_state_id)
              ->pluck('wf_state_current_id')
              ->toArray(), // Devuelve un array de IDs de los estados anteriores
          'next' => $this->wf_sequences()
              ->where('wf_state_current_id', $current_state_id)
              ->pluck('wf_state_next_id')
              ->toArray(), // Devuelve un array de IDs de los estados siguientes
      ];
  }

  // add records
  public function records()
  {
      return $this->morphMany(Record::class, 'recordable')->latest('updated_at');
  }

  public function procedure_modality()
  {
      return $this->hasMany(ProcedureModality::class, 'workflow_id');
  }

  public function get_flow()
  {
      return $this->wf_sequences()->get();
  }

  public function get_sequence()
  {
    return $ordered_sequences = $this->sortWorkflowSequences($this->wf_sequences);
    $ordered_sequences->transform(function ($wf_sequence) {
        return self::append_data($wf_sequence);
    });
    return $ordered_sequences;
  }

  private function sortWorkflowSequences($sequences)
  {
      $sequences = collect($sequences);
      $lookup = $sequences->keyBy('wf_state_current_id');
      $start = null;
      foreach ($sequences as $seq) {
          $isStart = true;
          foreach ($sequences as $otherSeq) {
              if ($otherSeq->wf_state_next_id === $seq->wf_state_current_id) {
                  $isStart = false;
                  break;
              }
          }
          if ($isStart) {
              $start = $seq;
              break;
          }
      }
      if (!$start) {
          return collect();
      }
      $sorted = collect();
      $current = $start;
      $sequenceNumber = 1;
      while ($current) {
          $current->number_sequence = $sequenceNumber;
          $sorted->push($current);
          $sequenceNumber++;
          $current = $lookup->get($current->wf_state_next_id);
      }
      return $sorted;
  }
}
