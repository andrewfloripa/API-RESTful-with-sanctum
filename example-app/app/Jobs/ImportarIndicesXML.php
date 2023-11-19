<?php

namespace App\Jobs;

use App\Models\Indice;
use App\Models\Livro;
use SimpleXMLElement;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ImportarIndicesXML implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $livroId;
    protected $xmlContent;

    /**
     * Create a new job instance.
     */
    public function __construct($livroId, $xmlContent)
    {
        $this->livroId = $livroId;
        $this->xmlContent = $xmlContent;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $xml = new SimpleXMLElement($this->xmlContent);
        $this->importarIndices($xml, null);
    }

     /**
     * Importa os índices de um elemento XML.
     *
     * @param SimpleXMLElement $element
     * @param int|null $indicePaiId
     */
    protected function importarIndices(SimpleXMLElement $element, $indicePaiId)
    {
        foreach ($element->item as $item) {
            // Cria o índice
            $indice = Indice::create([
                'livro_id' => $this->livroId,
                'titulo' => (string)$item['titulo'],
                'pagina' => (int)$item['pagina'],
                'indice_pai_id' => $indicePaiId
            ]);

            // Verifica se o item tem subitens e os importa também
            if ($item->count() > 0) {
                $this->importarIndices($item, $indice->id);
            }
        }
    }
}
