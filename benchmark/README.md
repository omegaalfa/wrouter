# Omegaalfa Wrouter — Benchmark Oficial

Este diretório contém o benchmark completo do **Omegaalfa Wrouter**, comparando sua performance com variados roteadores PHP modernos e clássicos.  
O teste mede **throughput (req/s)**, **latência média (μs)**, **tempo total**, **uso de CPU** e **pico de memória**.

Os benchmarks seguem o estilo do projeto [`php-router-benchmark`](https://github.com/follestad/php-router-benchmark).

---

# 📊 Resultados Reais do Benchmark

Os testes abaixo foram executados com:

- **PHP 8.x**
- **Ubuntu WSL**
- **CPU Ryzen** (desktop)
- `--iterations=2000`, `--warmup=50` no primeiro teste
- Execução padrão (sem argumentos) no segundo teste

Esses resultados mostram o desempenho real atingido pelo Wrouter.

---

## 🚀 Execução 1
Comando:

```bash
php benchmark/bench.php --method=GET --iterations=2000 --warmup=50

```

---

# 📊 Resultados do Benchmark

## Benchmark: Rota simples

```
Router                    Duração    Req/s        Latência    CPU         
--------------------------------------------------------------------------------
Regex Router              0.0113 s     266023       3.76 μs     0.0103
Naive Router              0.0121 s     248648       4.02 μs     0.0110
Omegaalfa Wrouter         0.0177 s     169934       5.88 μs     0.0161
Jaunt Router              0.0204 s     147368       6.79 μs     0.0185
Symfony (Compilado)       0.0217 s     138544       7.22 μs     0.0197
Phroute Router            0.0228 s     131476       7.61 μs     0.0208
Slim Framework            0.0984 s     30488        32.80 μs    0.0895

```

## Benchmark: Rota estática curta

```
Router                    Duração    Req/s        Latência    CPU         
--------------------------------------------------------------------------------
Omegaalfa Wrouter         0.0188 s     159948       6.25 μs     0.0171
Symfony (Compilado)       0.0210 s     142953       7.00 μs     0.0191
Phroute Router            0.0235 s     127748       7.83 μs     0.0214
Jaunt Router              0.0240 s     125125       7.99 μs     0.0218
Regex Router              0.0749 s     40051        24.97 μs    0.0681
Slim Framework            0.0971 s     30907        32.35 μs    0.0882
Naive Router              0.2260 s     13272        75.35 μs    0.2055

```

## Benchmark: Rota dinâmica — 1 parâmetro

```
Router                    Duração    Req/s        Latência    CPU         
--------------------------------------------------------------------------------
Omegaalfa Wrouter         0.0226 s     132757       7.53 μs     0.0206
Jaunt Router              0.0274 s     109400       9.14 μs     0.0249
Symfony (Compilado)       0.0331 s     90733        11.02 μs    0.0301
Phroute Router            0.0403 s     74418        13.44 μs    0.0367
Slim Framework            0.1193 s     25148        39.77 μs    0.1084
Naive Router              0.7885 s     3805         262.84 μs   0.7209
Regex Router              1.3479 s     2226         449.29 μs   1.2315

```

## Benchmark: Rota dinâmica — 2 parâmetros

```
Router                    Duração    Req/s        Latência    CPU         
--------------------------------------------------------------------------------
Omegaalfa Wrouter         0.0260 s     115550       8.65 μs     0.0238
Jaunt Router              0.0327 s     91836        10.89 μs    0.0296
Symfony (Compilado)       0.0510 s     58817        17.00 μs    0.0467
Phroute Router            0.2424 s     12376        80.80 μs    0.2217
Slim Framework            0.3454 s     8685         115.14 μs   0.3158
Naive Router              1.3736 s     2184         457.86 μs   1.2559
Regex Router              2.7693 s     1083         923.09 μs   2.5320

```

## Benchmark: Rota estática profunda

```
Router                    Duração    Req/s        Latência    CPU         
--------------------------------------------------------------------------------
Omegaalfa Wrouter         0.0184 s     162773       6.14 μs     0.0169
Symfony (Compilado)       0.0207 s     144893       6.90 μs     0.0189
Phroute Router            0.0227 s     132008       7.58 μs     0.0208
Jaunt Router              0.0283 s     106069       9.43 μs     0.0259
Slim Framework            0.0960 s     31246        32.00 μs    0.0878
Regex Router              6.0656 s     495          2021.88 μs  5.5573
Naive Router              7.3462 s     408          2448.73 μs  6.7250

```

---

# 🧪 Sobre o Benchmark CLI

O benchmark deste diretório compara o **Omegaalfa Wrouter** com diferentes modelos clássicos de roteamento.

Ele segue o espírito do benchmark de Follestad (`php-router-benchmark`), registrando rotas, simulando tráfego e medindo:

* latência média (μs)
* throughput (req/s)
* uso de CPU
* pico de memória

### 🔍 Roteadores testados

* **Omegaalfa Wrouter** — roteador baseado em árvore otimizado
* **Naive Router** — iteração linear simples
* **Regex Router** — combinações via regex pré-compiladas

### 🎛️ Funcionamento Interno

O benchmark utiliza:

* warm-up configurável
* `getrusage()` para CPU
* `memory_get_peak_usage(true)` para memória
* resoluções determinísticas para parâmetros como `:id`

---

# ▶️ Uso

```
php benchmark/bench.php \
  [--method=GET] \
  [--path=/benchmark/:id] \
  [--body='{"foo":"bar"}'] \
  [--iterations=1000] \
  [--warmup=20]

```

### Opções principais

* `--method` — método HTTP
* `--path` — rota com placeholders
* `--body` — JSON enviado como corpo
* `--iterations` — número de requisições medidas
* `--warmup` — número de chamadas prévias de aquecimento

---

# 💡 Exemplo

```
php benchmark/bench.php --method=GET --iterations=2000 --warmup=50
```

Este comando gera métricas completas para todos os roteadores, incluindo l
