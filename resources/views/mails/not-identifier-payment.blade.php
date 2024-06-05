<h2>Olá, Administrador da ANIPP!</h2>

<p>Segue os pagamentos não identificados:</p>

<ul>
    @foreach($infos as $document)
        <li>{{ $document['document_number'] }} - Valor: {{ $document['value'] }}</li>
    @endforeach
</ul>

<p>Não responda a este e-mail. Em caso de dúvidas, entre em contato por outros meios.</p>
