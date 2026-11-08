@props(['disabled' => false])

<input {{ $disabled ? 'disabled' : '' }} {!! $attributes->merge(['class' => 'border-gray-300 dark:border-slate-700/80 bg-white dark:bg-slate-900/90 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-slate-500 focus:border-indigo-500 focus:ring-indigo-500 dark:focus:ring-indigo-500/30 rounded-xl shadow-sm text-sm']) !!}>
