Leitura do estudo de caso:
Considerando um contexto onde a estrutura que consome as Jobs e serve aos usuários em um único servidor, tendo apenas um servidor MySQL, ou seja, basicamente um monolito.

Primeiramente, minha sugestão é separar o consumo das Jobs e o serviço aos usuários em servidores diferentes, assim o servidor que consome as Jobs ficaria com alto consumo de CPU e memória, enquanto o serviço aos usuários continuaria com baixo consumo.

Mesmo resolvendo esse problema de infraestrutura, continuaríamos tendo problemas com I/O no Banco de dados, principalmente considerando o script disponível nesse projeto, onde um comando lê um seller, captura o token e envia para uma job que salva apenas 1 registro.
Nesse caso, eu analisaria a possibilidade de usar operações em batch/bulk, diminuindo a carga do pool de conexões, já que teríamos apenas uma conexão. Ainda, poderíamos evitar a sobrecarga dos usuários, criando BDs réplicas para leitura apenas, assim o serviço aos usuários retornaria dados dessa réplica, enquanto o consumer de Jobs salvaria no original, evitando assim delay ou problemas de LOCK no BD durante requisições de usuários.

Ainda assim, caso não seja usado hoje, seria importante trabalhar com cache e talvez evitando re-salvar produtos que foram atualizados há pouco tempo (isso dependeria da regra de negócio e do tipo de sistema/cliente)


-- A partir daqui não estou mais respondendo o estudo de caso, apenas mostrando uma de minhas experiências --

Tive um problema parecido em um sistema que capturava os CNPJs do governo, apesar de não precisar consumir uma API, mas sim vários arquivos CSV, a carga de trabalho salvando esses dados era parecido. Ao finalizar todo o processo, o BD tinha ao todo mais de 18 milhões de registros que foram salvos em menos de uma hora.

Script prontos disponíveis no GitHub traziam informação que terminaria em 6 horas o processo usando python. Me baseando nesses scripts para entender o que significavam os dados de cada arquivo, montei um script em Node.js, trabalhando fortemente com child-exec e Promises.
Rodava vários micro-processos ao mesmo tempo, para diminuir o tempo de leitura e tratamento dos dados, mas comecei a ter problemas com MySQL ao salvar. Para resolver isso, trabalhei de duas formas, mantive um child-exec apenas para comunicar com o Banco de Dados, que recebia as requisições para salvar, ele montava a query de insert, eu juntei várias manualmente (provavelmente tem formas melhores de fazer isso) e limitei à cerca de 5k registros. Em conjunto, usei bastante das transactions, fazendo commits a cada 50k, ou seja, a cada 10 vezes que o child-exec enviava para o Banco de Dados.
Cheguei a salvar meio milhão de registros por minuto, diminuindo as 6 horas para menos da metade. Claro que evitei vários tratamentos de dados, relacionamentos importantes e índices que facilitariam no momento da leitura desses mesmos dados. Depois de adicionadas essas validações, o tempo acabou aumentando mas por conta do MySQL demorar para fazer algumas operações como criar índices fulltext e validação de FK. Mas ainda assim o tempo continuou menor que todos os tempos indicados em projetos já existentes.