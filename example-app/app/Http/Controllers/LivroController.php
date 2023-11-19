<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Livro;
use App\Models\Indice;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\LivroResource;
use App\Http\Resources\IndiceResource;
use App\Jobs\ImportarIndicesXML;
use Illuminate\Support\Facades\Validator;
use SimpleXMLElement;
use Illuminate\Http\JsonResponse;

class LivroController extends Controller
{   
    public function index(Request $request)
    {
        // Filtrar pelo título do livro
        if ($titulo = $request->query('titulo')) {
            $query = Livro::query()->with(['usuarioPublicador']);

            $livro = $query->where('titulo', 'like', '%'.$titulo.'%')->get();

            return LivroResource::collection($livro);
        }

        // Filtrar pelo título do índice.
        if ($tituloDoIndice = $request->query('titulo_do_indice')) {
            $indice = Indice::with('indicePai.indicePai') // Isso irá carregar o índice pai e o índice "avô" também
                        ->where('titulo', $tituloDoIndice)
                        ->first();
        
            if ($indice) {
                // Inicia com o índice atual
                $indicesIds = [$indice->id];
        
                // Agora, rastreamos a hierarquia para cima, adicionando cada índice pai
                while ($indice->indicePai) {
                    $indicesIds[] = $indice->indicePai->id;
                    $indice = $indice->indicePai;
                }
        
                // Carregue o livro associado com o índice encontrado e todos os índices pais
                $livro = Livro::where('id', $indice->livro_id)
                              ->with(['indices' => function ($query) use ($indicesIds) {
                                  $query->whereIn('id', $indicesIds);
                              }])
                              ->first();
        
                if ($livro) {
                    return new LivroResource($livro);
                }
            }
        }
        // Sem Filtro
        return LivroResource::collection(Livro::query()->with(['usuarioPublicador'])->get());
    }

    public function store(Request $request)
    {
        $request->validate([
            'titulo' => 'required|string|max:255',
        ]);

        DB::beginTransaction(); // Inicia a transaction

        try {
            $livro = Livro::create([
                'titulo' => $request->titulo,
                'usuario_publicador_id' => auth()->id(),
            ]);

            $errors = $this->validarIndices($request->indices);

            if (count($errors) > 0) {
                DB::rollBack(); // Desfaz se houver erros de validação
                return response()->json(['errors' => $errors], 422);
            }

            $this->salvarIndices($request->indices, null, $livro->id);

            DB::commit(); // Commita
            return response()->json($livro, 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['message' => 'Erro ao salvar o livro e índices.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Valida recursivamente uma estrutura de índices e subíndices.
     *
     * @param array $indices Os índices a serem validados.
     * @param string $path O caminho de base usado para rastrear a localização do índice na estrutura.
     * @return array Uma array de erros de validação, se houver.
     */
    private function validarIndices($indices, $path = 'indices')
    {
        $errors = [];

        foreach ($indices as $key => $indice) {
            $currentPath = "{$path}[{$key}]";

            $validator = Validator::make($indice, [
                'titulo' => 'required|string|max:255',
                'pagina' => 'required|numeric',
            ]);

            if ($validator->fails()) {
                $errors[$currentPath] = $validator->errors()->toArray();
            }

            // Valida os subíndices se existirem
            if (isset($indice['subindices'])) {
                $subindiceErrors = $this->validarIndices($indice['subindices'], "{$currentPath}.subindices");
                $errors = array_merge($errors, $subindiceErrors);
            }
        }

        return $errors;
    }

    /**
     * Salva recursivamente os índices e subíndices em um banco de dados.
     *
     * @param array $indices A lista de índices a serem salvos.
     * @param int|null $indicePaiId O ID do índice pai, se o índice atual for um subíndice.
     * @param int $livroId O ID do livro ao qual os índices estão associados.
     */
    private function salvarIndices($indices, $indicePaiId, $livroId)
    {
        foreach ($indices as $indiceData) {
            $indice = Indice::create([
                'titulo' => $indiceData['titulo'],
                'pagina' => $indiceData['pagina'],
                'livro_id' => $livroId,
                'indice_pai_id' => $indicePaiId,
            ]);

            if (!empty($indiceData['subindices'])) {
                $this->salvarIndices($indiceData['subindices'], $indice->id, $livroId);
            }
        }
    }

    /**
     * Controlador que lida com a importação de índices de um livro a partir de um arquivo XML.
     *
     * @param Request $request A requisição HTTP.
     * @param int $livroId O id do livro para o qual os índices serão importados.
     * @return JsonResponse Resposta JSON.
     */
    public function importar(Request $request, $livroId): JsonResponse
    {   
        $livro = Livro::findOrFail($livroId);

        // Analisa o conteúdo XML da requisição
        $xmlContent = $request->getContent();
       
        $xml = new SimpleXMLElement($xmlContent);

        // Valida a entrada
        $validationErrors = [];
        $this->validateXmlItems($xml, $validationErrors);

        if (!empty($validationErrors)) {
            // Retorna um erro de validação
            return response()->json(['errors' => $validationErrors], 422);
        }
        
        // Despacha o job
        ImportarIndicesXML::dispatch($livroId, $xmlContent);

        return response()->json(['message' => 'A importação dos índices XML com sucesso!'], 202);
    }

    /**
     * Valida recursivamente os elementos XML para garantir que todos os índices e subíndices
     * tenham os campos 'titulo' e 'pagina' corretamente preenchidos.
     *
     * @param SimpleXMLElement $items Os itens de índice XML a serem validados.
     * @param array &$errors Array de erros de validação.
     * @param string $path Caminho atual na estrutura de índices usado para rastrear onde ocorre um erro de validação.
     */
    private function validateXmlItems($items, &$errors, $path = 'índice')
    {
        foreach ($items as $index => $item) {
            $currentPath = "{$path} > item {$index}";

            // Pega os atributos para validação
            $attributes = [
                'titulo' => (string)$item['titulo'],
                'pagina' => (string)$item['pagina'],
            ];

            // Regras de validação
            $validator = Validator::make($attributes, [
                'titulo' => 'required|string',
                'pagina' => 'required|numeric',
            ]);

            // Se a validação falhar, adiciona o erro à lista
            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $error) {
                    $errors[] = ["{$currentPath}" => $error];
                }
            }

            // Valida os subitens, se existirem
            if ($item->item) {
                $this->validateXmlItems($item->item, $errors, $currentPath);
            }
        }
    }
}
