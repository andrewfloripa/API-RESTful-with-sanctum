<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Livro;

class LivroControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testStoreMethodWithAuthentication()
    {
        // Obtem o token
        $token = $this->getToken();

        // Dados do livro e índices a serem enviados
        $dadosLivro = [
            'titulo' => 'Novo Livro',
            'indices' => [
                [
                    'titulo' => 'Índice 1',
                    'pagina' => 1,
                    'subindices' => [
                        [
                            'titulo' => 'Subíndice 1.1',
                            'pagina' => 2
                        ],                      
                    ]
                ],
            ]
        ];

        // Envia a requisição POST para o endpoint store do LivroController
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->post('/api/v1/livros', $dadosLivro);

        // Verifica se a resposta é a esperada
        $response->assertStatus(201); // Verifica se o status da resposta.
        $response->assertJson(['titulo' => 'Novo Livro']); // Verifica se o título do livro retornado é correto

        // Verifica se o livro e os índices foram criados no banco de dados
        $this->assertDatabaseHas('livros', ['titulo' => 'Novo Livro']);
        $this->assertDatabaseHas('indices', ['titulo' => 'Índice 1']);
        $this->assertDatabaseHas('indices', ['titulo' => 'Subíndice 1.1']);
    }

    public function testImportar()
    {
        // Obtem o token
        $token = $this->getToken();

        // Cria um livro para teste
        $livro = Livro::factory()->create();

        // Conteúdo XML de exemplo para importação
        $xmlContent = '<indice>
                            <item pagina="1" titulo="Seção 1">
                                <item pagina="1" titulo="Seção 1.1.1"/>            
                            </item>       
                        </indice>';

        // Prepara os cabeçalhos para a requisição
        $headers = [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/xml',
        ];

        // Envia a requisição POST para o endpoint de importação
        $response = $this->call('POST', "/api/v1/livros/{$livro->id}/importar-indices-xml", [], [], [], $headers, $xmlContent);

        // Verifica se a resposta é a esperada
        $response->assertStatus(202); // Verifica se o status da resposta.
        $response->assertJson(['message' => 'A importação dos índices XML com sucesso!']);

        // Verifica se os índices foram realmente criados no banco de dados
        $this->assertDatabaseHas('indices', ['titulo' => 'Seção 1']);
        $this->assertDatabaseHas('indices', ['titulo' => 'Seção 1.1.1']);
    }

    public function testIndex()
    {
        // Cria alguns livros para teste
        $livros = Livro::factory()->count(5)->create();

        // Obtem o token
        $token = $this->getToken();

        // Envia a requisição GET para o endpoint de listagem de livros
        $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->get('/api/v1/livros');

        // Verifica se a resposta é a esperada
        $response->assertStatus(200); // Verifica se o status da resposta
        $response->assertJsonCount(5, 'data'); // Verifica se cinco livros são retornados na resposta
        $response->assertJsonStructure([ // Verifica a estrutura JSON da resposta
            'data' => [
                '*' => [
                    'id', 
                    'titulo', 
                    'usuario_publicador' => [
                        'id', 
                        'nome',                         
                    ],                    
                ]
            ]
        ]);

        // Verifica se os títulos dos livros criados
        foreach ($livros as $livro) {
            $response->assertJsonFragment(['titulo' => $livro->titulo]);
        }
    }

    private function getToken()
    {
        // Cria um usuário utilizando a factory
        $user = User::factory()->create([
            'email' => 'test@exemplo.com',
            'password' => bcrypt('password')
        ]);

        // Obtenha o token de autenticação (ajuste conforme o seu método de autenticação)
        $response = $this->post('/api/v1/auth/token', [
            'email' => 'test@exemplo.com',
            'password' => 'password',
            'device_name' => 'testDevice' //Sanctum, é necessário
        ]);

        return $response->json('token');
    }
}

